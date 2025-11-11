<?php
class Sources
{
    public $connection;

    public function __construct() {
        global $connection;
        $this->connection = $connection;
    }

    /**
     * Get all sources with their subsources
     */
    public function getAllSources() 
    {
        $list = [];
        $query = "
            SELECT 
                s.id AS source_id,
                s.source AS source_name,
                s.active AS source_active,
                s.pm_flag AS pm_flag,
                s.sm_flag AS sm_flag,
                s.is_selected as is_selected,
                sub.id AS subsource_id,
                sub.sub_source AS subsource_name,
                sub.active AS subsource_active
            FROM master_sources s
            LEFT JOIN master_sources_sub sub 
                ON s.id = sub.source_id
            ORDER BY s.source ASC, sub.sub_source ASC
        ";
        
        $result = mysqli_query($this->connection, $query);
        if (!$result) {
            logSqlError(mysqli_error($this->connection), $query, 'sources-get', true);
            return ["list" => []];
        }

        while ($row = mysqli_fetch_assoc($result)) {
            $srcId = $row['source_id'];

            if (!isset($list[$srcId])) {
                $list[$srcId] = [
                    'source_id'  => $row['source_id'],
                    'name'       => $row['source_name'],
                    'active'     => $row['source_active'],
                    'pm_flag'     => $row['pm_flag'],
                    'sm_flag'     => $row['sm_flag'],
                    'is_selected' =>$row['is_selected'],
                    'subsources' => []
                ];
            }

            if (!empty($row['subsource_id'])) {
                $list[$srcId]['subsources'][] = [
                    'subsource_id' => $row['subsource_id'],
                    'name'         => $row['subsource_name'],
                    'active'       => $row['subsource_active']
                ];
            }
        }

        return ["list" => array_values($list)];
    }

    /**
     * Check if a source exists by name
     */
    public function isSourceExists($name, $excludeId = null) {
        $name = mysqli_real_escape_string($this->connection, trim($name));
        $query = "SELECT id FROM master_sources WHERE source = '$name'";
        if ($excludeId) {
            $query .= " AND id != '".intval($excludeId)."'";
        }
        $res = mysqli_query($this->connection, $query);
        return mysqli_num_rows($res) > 0;
    }

    /**
     * Add a new source
     */
    public function addSource($data=[]) {
        $name = $data['name']? trim($data['name']):'';
        $active = $data['active']?trim($data['active']):0;
        $pm_flag=$data['pm_flag']?trim($data['pm_flag']):0;
        $sm_flag=$data['sm_flag']?trim($data['sm_flag']):0;
        if ($this->isSourceExists($name)) {
            return ['success' => false, 'error' => 'Source already exists'];
        }

        $nameEsc = mysqli_real_escape_string($this->connection, $name);
        $activeEsc = mysqli_real_escape_string($this->connection, $active);
        $pmFlagEsc = mysqli_real_escape_string($this->connection, $pm_flag);
        $smFlagEsc = mysqli_real_escape_string($this->connection, $sm_flag);

        $query = "INSERT INTO master_sources (source, active,pm_flag,sm_flag) VALUES ('$nameEsc', '$activeEsc','$pm_flag','$sm_flag')";
        if (!mysqli_query($this->connection, $query)) {
            logSqlError(mysqli_error($this->connection), $query, 'sources-add', true);
            return ['success' => false, 'error' => 'DB insert failed'];
        }
        return ['success' => true, 'id' => mysqli_insert_id($this->connection)];
    }

    /**
     * Update existing source
     */
    public function updateSource($data=[]) {
        $id = $data['id']?intval($data['id']):0;
        $active = $data['active']?trim($data['active']):0;
        $name = $data['name']?trim($data['name']):'';
        $pm_flag=$data['pm_flag']?trim($data['pm_flag']):0;
        $sm_flag=$data['sm_flag']?trim($data['sm_flag']):0;
        $is_selected=$data['is_selected']?trim($data['is_selected']):0;

        if ($this->isSourceExists($name, $id)) {
            return ['success' => false, 'error' => 'Source already exists'];
        }

        $nameEsc = mysqli_real_escape_string($this->connection, $name);
        $activeEsc = mysqli_real_escape_string($this->connection, $active);
        $pmFlagEsc = mysqli_real_escape_string($this->connection, $pm_flag);
        $smFlagEsc = mysqli_real_escape_string($this->connection, $sm_flag);
        $is_selected = mysqli_real_escape_string($this->connection, $is_selected);

        $query = "UPDATE master_sources SET source = '$nameEsc', active = '$activeEsc',pm_flag = '$pmFlagEsc',sm_flag = '$smFlagEsc',is_selected = '$is_selected' WHERE id = $id";
        if (!mysqli_query($this->connection, $query)) {
            logSqlError(mysqli_error($this->connection), $query, 'sources-update', true);
            return ['success' => false, 'error' => 'DB update failed'];
        }
        return ['success' => true];
    }

    /**
     * Check if a subsource exists
     */
    public function isSubsourceExists($name, $sourceId, $excludeId = null) {
        $name = mysqli_real_escape_string($this->connection, trim($name));
        $sourceId = intval($sourceId);
        $query = "SELECT id FROM master_sources_sub WHERE sub_source = '$name' AND source_id = $sourceId";
        if ($excludeId) {
            $query .= " AND id != '".intval($excludeId)."'";
        }
        $res = mysqli_query($this->connection, $query);
        return mysqli_num_rows($res) > 0;
    }

    /**
     * Add a subsource to a source
     */
    public function addSubsource($sourceId, $name, $active = 'y') {
        $sourceId = intval($sourceId);
        $name = trim($name);

        if ($this->isSubsourceExists($name, $sourceId)) {
            return ['success' => false, 'error' => 'Subsource already exists'];
        }

        $nameEsc = mysqli_real_escape_string($this->connection, $name);
        $activeEsc = mysqli_real_escape_string($this->connection, $active);

        $query = "INSERT INTO master_sources_sub (source_id, sub_source, active) VALUES ($sourceId, '$nameEsc', '$activeEsc')";
        if (!mysqli_query($this->connection, $query)) {
            logSqlError(mysqli_error($this->connection), $query, 'subsource-add', true);
            return ['success' => false, 'error' => 'DB insert failed'];
        }
        return ['success' => true, 'id' => mysqli_insert_id($this->connection)];
    }

    /**
     * Update a subsource
     */
    public function updateSubsource($id, $sourceId, $name, $active) {
        $id = intval($id);
        $sourceId = intval($sourceId);
        $name = trim($name);

        if ($this->isSubsourceExists($name, $sourceId, $id)) {
            return ['success' => false, 'error' => 'Subsource already exists'];
        }

        $nameEsc = mysqli_real_escape_string($this->connection, $name);
        $activeEsc = mysqli_real_escape_string($this->connection, $active);

        $query = "UPDATE master_sources_sub SET sub_source = '$nameEsc', active = '$activeEsc' WHERE id = $id AND source_id = $sourceId";
        if (!mysqli_query($this->connection, $query)) {
            logSqlError(mysqli_error($this->connection), $query, 'subsource-update', true);
            return ['success' => false, 'error' => 'DB update failed'];
        }
        return ['success' => true];
    }
}
?>
