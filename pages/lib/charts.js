// Load Chart.js dynamically
export async function loadChartJS() {
  if (!window.Chart) {
    await import(`${window.g?.$base_url}/assets/js/chart.umd.js?v=${window.g?.$ver}`);
  }
  return window.Chart;
}

// Refined modern color palette for charts and UI (black/greyish theme)
const COLORS = {
  primary: '#424242',
  light: '#F5F5F5',
  white: '#FFFFFF',
  dark: '#212121',
  muted: '#E0E0E0',
  chart: ['#424242', '#757575', '#BDBDBD', '#E0E0E0'],
  chartBackground: '#FFFFFF',
};

// Enhanced canvas context check with destruction flag
function isValidCtx(ctx) {
  if (!ctx) return false;
  if (typeof ctx.canvas === 'undefined') return false;
  if (!(ctx.canvas instanceof HTMLCanvasElement)) return false;
  if (typeof ctx.save !== 'function') return false;
  
  // Check if canvas is marked as being destroyed
  if (ctx.canvas.dataset && ctx.canvas.dataset.destroying === 'true') {
    return false;
  }
  
  return true;
}

// Wrap Chart constructor to add destruction safeguards
function createSafeChart(ctx, config) {
  if (!isValidCtx(ctx)) {
    console.warn('Invalid canvas context passed to chart creation.');
    return null;
  }

  try {
    const chart = new Chart(ctx, config);
    
    // Store original destroy method
    const originalDestroy = chart.destroy.bind(chart);
    
    // Override destroy method with safeguards
    chart.destroy = function() {
      try {
        // Mark canvas as being destroyed
        if (this.canvas) {
          this.canvas.dataset.destroying = 'true';
        }
        
        // Stop all animations
        if (this.stop) {
          this.stop();
        }
        
        // Clear the canvas
        if (this.ctx) {
          try {
            this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
          } catch (e) {
            // Ignore errors during cleanup
          }
        }
        
        // Call original destroy
        originalDestroy();
        
        // Nullify references
        this.ctx = null;
        this.canvas = null;
      } catch (e) {
        console.warn('Error in chart destroy:', e);
      }
    };
    
    // Add safety check to render loop
    const originalDraw = chart.draw;
    chart.draw = function() {
      // Only draw if canvas is still valid and not being destroyed
      if (this.canvas && this.canvas.dataset.destroying !== 'true' && isValidCtx(this.ctx)) {
        try {
          originalDraw.call(this);
        } catch (e) {
          console.warn('Error during chart draw:', e);
        }
      }
    };
    
    return chart;
  } catch (e) {
    console.error('Error creating chart:', e);
    return null;
  }
}

// Apply fallback colors to datasets if not specified
function applyDefaultColors(data) {
  if (data?.datasets) {
    data.datasets = data.datasets.map((ds, i) => ({
      ...ds,
      backgroundColor: ds.backgroundColor || COLORS.chart[i % COLORS.chart.length],
      borderColor: ds.borderColor || COLORS.chart[i % COLORS.chart.length],
      borderWidth: ds.borderWidth ?? 1,
      pointBackgroundColor: ds.pointBackgroundColor || COLORS.chart[i % COLORS.chart.length],
      pointBorderColor: ds.pointBorderColor || COLORS.white,
    }));
  }
  return data;
}

// Extract min/max from data for better y-axis scaling
function getSuggestedMinMax(data) {
  const allValues = data.datasets.flatMap(ds => ds.data);
  const min = Math.min(...allValues);
  const max = Math.max(...allValues);

  return {
    suggestedMin: Math.floor(min - (max - min) * 0.1),
    suggestedMax: Math.ceil(max + (max - min) * 0.1),
  };
}

// Get site font family from CSS if available, or default
function getSiteFontFamily() {
  if (typeof document !== 'undefined') {
    const bodyStyles = getComputedStyle(document.body);
    return bodyStyles.fontFamily || 'sans-serif';
  }
  return 'sans-serif';
}

const siteFontFamily = getSiteFontFamily();

// Base chart options with minimalist theme and responsive scaling
export function baseChartOptions({ title = '', responsive = true, suggestedMin, suggestedMax } = {}) {
  return {
    responsive,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'bottom',
        labels: {
          boxWidth: 10,
          color: COLORS.dark,
          font: {
            size: 12,
            weight: '500',
            family: siteFontFamily,
          },
          padding: 20,
        },
      },
      title: {
        display: !!title,
        text: title,
        color: COLORS.dark,
        font: {
          size: 18,
          weight: '600',
          family: siteFontFamily,
        },
        padding: { top: 10, bottom: 20 },
      },
      tooltip: {
        mode: 'index',
        intersect: false,
        backgroundColor: COLORS.dark,
        titleColor: COLORS.white,
        bodyColor: COLORS.light,
        borderColor: COLORS.muted,
        borderWidth: 1,
        bodyFont: {
          family: siteFontFamily,
        },
        titleFont: {
          family: siteFontFamily,
          weight: 'bold',
        }
      },
      datalabels: {
        anchor: 'end',
        align: 'end',
        color: COLORS.dark,
        font: {
          weight: '600',
          size: 12,
          family: siteFontFamily,
        },
        formatter: (value) => value,
      },
    },
    layout: { padding: 10 },
    scales: {
      x: {
        grid: { display: false, drawBorder: false },
        ticks: {
          color: COLORS.dark,
          autoSkip: true,
          maxTicksLimit: 8,
          font: {
            family: siteFontFamily,
          },
        },
      },
      y: {
        beginAtZero: false,
        suggestedMin,
        suggestedMax,
        grid: {
          color: COLORS.muted,
          drawBorder: false,
        },
        ticks: {
          color: COLORS.dark,
          maxTicksLimit: 5,
          callback: (value) => Number.isInteger(value) ? value : value.toFixed(1),
          font: {
            family: siteFontFamily,
          },
        },
      },
    },
    elements: {
      arc: {
        backgroundColor: COLORS.chartBackground,
      },
      rectangle: {
        backgroundColor: COLORS.chartBackground,
      },
    }
  };
}

// Create vertical bar chart
export function createBarChart(ctx, data, options = {}) {
  if (!isValidCtx(ctx)) {
    console.warn('Invalid canvas context passed to createBarChart.');
    return null;
  }

  const { suggestedMin, suggestedMax } = getSuggestedMinMax(data);

  return createSafeChart(ctx, {
    type: 'bar',
    data: applyDefaultColors(data),
    options: {
      ...baseChartOptions({ ...options, suggestedMin, suggestedMax }),
      ...options,
      scales: {
        x: {
          ...baseChartOptions().scales.x,
          ...options?.scales?.x,
          grid: {
            display: false,
            drawBorder: false,
          },
        },
        y: {
          ...baseChartOptions().scales.y,
          ...options?.scales?.y,
          grid: {
            color: COLORS.muted,
            drawBorder: false,
          },
        },
      },
    },
  });
}

// Create horizontal bar chart
export function createHorizontalBarChart(ctx, data, options = {}) {
  if (!isValidCtx(ctx)) {
    console.warn('Invalid canvas context passed to createHorizontalBarChart.');
    return null;
  }

  const { suggestedMin, suggestedMax } = getSuggestedMinMax(data);
  return createSafeChart(ctx, {
    type: 'bar',
    data: applyDefaultColors(data),
    options: {
      ...baseChartOptions({ ...options, suggestedMin, suggestedMax }),
      indexAxis: 'y',
      ...options,
      scales: {
        x: {
          ...baseChartOptions().scales.x,
          ...options?.scales?.x,
          grid: {
            color: COLORS.muted,
            drawBorder: false,
          },
        },
        y: {
          ...baseChartOptions().scales.y,
          ...options?.scales?.y,
          grid: {
            display: false,
            drawBorder: false,
          },
        },
      },
    },
  });
}

// Create line chart
export function createLineChart(ctx, data, options = {}) {
  if (!isValidCtx(ctx)) {
    console.warn('Invalid canvas context passed to createLineChart.');
    return null;
  }

  const { suggestedMin, suggestedMax } = getSuggestedMinMax(data);
  return createSafeChart(ctx, {
    type: 'line',
    data: applyDefaultColors(data),
    options: {
      ...baseChartOptions({ ...options, suggestedMin, suggestedMax }),
      ...options,
      elements: {
        line: {
          tension: 0.3,
          borderWidth: 2,
        },
        point: {
          radius: 3,
          hoverRadius: 5,
          backgroundColor: COLORS.chartBackground,
          borderColor: COLORS.primary,
          borderWidth: 1,
        }
      },
      scales: {
        x: {
          ...baseChartOptions().scales.x,
          ...options?.scales?.x,
          grid: {
            color: COLORS.muted,
            drawBorder: false,
          },
        },
        y: {
          ...baseChartOptions().scales.y,
          ...options?.scales?.y,
          grid: {
            color: COLORS.muted,
            drawBorder: false,
          },
        },
      },
    },
  });
}

// Create pie chart
export function createPieChart(ctx, data, options = {}) {
  if (!isValidCtx(ctx)) {
    console.warn('Invalid canvas context passed to createPieChart.');
    return null;
  }

  return createSafeChart(ctx, {
    type: 'pie',
    data: applyDefaultColors(data),
    options: {
      ...baseChartOptions({ responsive: true }),
      ...options,
      plugins: {
        ...baseChartOptions().plugins,
        ...options?.plugins,
        legend: {
          position: 'bottom',
          labels: {
            color: COLORS.dark,
            boxWidth: 12,
            font: {
              family: siteFontFamily,
            },
          },
        },
        datalabels: {
          color: COLORS.white,
          textShadowColor: 'rgba(0,0,0,0.5)',
          textShadowBlur: 2,
        },
      },
      scales: {
        x: { display: false },
        y: { display: false }
      },
    },
  });
}

// Create doughnut chart
export function createDoughnutChart(ctx, data, options = {}) {
  if (!isValidCtx(ctx)) {
    console.warn('Invalid canvas context passed to createDoughnutChart.');
    return null;
  }

  return createSafeChart(ctx, {
    type: 'doughnut',
    data: applyDefaultColors(data),
    options: {
      ...baseChartOptions({ responsive: true }),
      ...options,
      plugins: {
        ...baseChartOptions().plugins,
        ...options?.plugins,
        legend: {
          position: 'bottom',
          labels: {
            color: COLORS.dark,
            boxWidth: 12,
            font: {
              family: siteFontFamily,
            },
          },
        },
        datalabels: {
          color: COLORS.white,
          textShadowColor: 'rgba(0,0,0,0.5)',
          textShadowBlur: 2,
        },
      },
      scales: {
        x: { display: false },
        y: { display: false }
      },
    },
  });
}