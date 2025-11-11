<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_files.php';
use HeadlessChromium\BrowserFactory;

class chrome_headless_pdf
{
    private $files;

    private $landscape;
    private $printBackground;
    private $displayHeaderFooter;
    private $preferCSSPageSize;
    private $marginTop;
    private $marginBottom;
    private $marginLeft;
    private $marginRight;

    public function __construct() {
        $this->landscape = false;
        $this->printBackground = true;
        $this->displayHeaderFooter = false;
        $this->preferCSSPageSize = true;
        $this->marginTop = 0.0;
        $this->marginBottom = 0.0;
        $this->marginLeft = 0.0;
        $this->marginRight = 0.0;

    }

    private function normalizeUrl(string $url): string {
        return preg_match('#^https?://#', $url) ? $url : 'https://' . $url;
    }

    public function generate(string $url, string $fileName): array
    {
        $url = $this->normalizeUrl($url);

        $options = [
            'landscape'           => $this->landscape,
            'printBackground'     => $this->printBackground,
            'displayHeaderFooter' => $this->displayHeaderFooter,
            'preferCSSPageSize'   => $this->preferCSSPageSize,
            'marginTop'           => $this->marginTop,
            'marginBottom'        => $this->marginBottom,
            'marginLeft'          => $this->marginLeft,
            'marginRight'         => $this->marginRight
        ];

        $browserFactory = new BrowserFactory('google-chrome');
        $browser = $browserFactory->createBrowser(['headless' => true]);

        try {
            $page = $browser->createPage();
            $page->navigate($url)->waitForNavigation('networkIdle', 5000);

            $tempDir = $_SERVER['DOCUMENT_ROOT'] . '/export_temp/';
            if (!is_dir($tempDir)) mkdir($tempDir, 0755, true);

            $pdfPath = $tempDir . $fileName;
            $page->pdf($options)->saveToFile($pdfPath);

            if (!file_exists($pdfPath)) {
                return ['status' => 'fail', 'msg' => 'Failed to generate PDF'];
            }

            $publicUrl = '/export_temp/' . $fileName;

            return [
                'status' => 'success',
                'file_name' => $fileName,
                'file_url' => $publicUrl,
                'msg' => 'PDF generated successfully',
            ];
        } catch (\Throwable $e) {
            return ['status' => 'fail', 'msg' => 'Error generating PDF: ' . $e->getMessage()];
        } finally {
            $browser->close();
        }
    }

    public function generateAndUpload(string $url, string $fileName, string $targetDir = 'docs'): array
    {
        $url = $this->normalizeUrl($url);

        $browserFactory = new BrowserFactory('google-chrome');
        $browser = $browserFactory->createBrowser(['headless' => true]);

        try {
            $page = $browser->createPage();
            $page->navigate($url)->waitForNavigation();
            usleep(2000000); // small delay for dynamic content

            $pdfPath = sys_get_temp_dir() . '/' . $fileName;
            $page->pdf(['printBackground' => true])->saveToFile($pdfPath);

            // Populate $_FILES for upload
            $fileFieldKey = 'generated_pdf';
            $_FILES[$fileFieldKey] = [
                'name' => basename($pdfPath),
                'type' => 'application/pdf',
                'tmp_name' => $pdfPath,
                'error' => UPLOAD_ERR_OK,
                'size' => filesize($pdfPath),
            ];

            $uploadResult = $this->files->uploadFiles($fileFieldKey, $fileName, $targetDir);
            if (file_exists($pdfPath)) unlink($pdfPath);

            if (!empty($uploadResult['status']) && $uploadResult['status'] === true) {
                return [
                    'status' => 'success',
                    'file_name' => $uploadResult['file_name'] ?? $fileName,
                    'file_url' => $uploadResult['file_url'] ?? '',
                    'msg' => 'PDF generated and uploaded successfully'
                ];
            }

            return ['status' => 'fail', 'msg' => $uploadResult['msg'] ?? 'File upload failed'];
        } catch (\Throwable $e) {
            return ['status' => 'fail', 'msg' => 'Error generating PDF: ' . $e->getMessage()];
        } finally {
            $browser->close();
        }
    }
}
