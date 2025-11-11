<?php
require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

class Files
{
    private $s3_bucket;
    private $s3_init;
    public $image_allowed_ext = [];
    public $mimes = [];
    public $allowed_file_size;    
    public $allowed_folders;

    public function __construct()
    {
        global $config;
        // Check if S3 configuration exists
        if (isset($config['S3_bucket']) && isset($config['S3Access_key']) && isset($config['S3Scret_key'])) {
            $this->s3_bucket = $config['S3_bucket'];   

            // S3 initialization   
            try {
                $this->s3_init = new S3Client([
                    'version' => 'latest',
                    'region'  => 'ap-south-1',
                    'ssl' => true,
                ]);
            } catch (Exception $e) {
                error_log("S3 initialization failed: " . $e->getMessage());
                $this->s3_init = null;
            }
        } else {
            // S3 configuration missing - log warning but don't fail
            error_log("Warning: S3 configuration missing. File upload functionality may be limited.");
            $this->s3_bucket = null;
            $this->s3_init = null;
        }        
        $this->allowed_folders = ["vimages"=>"image","docs" =>["pdf","doc","image"]];
        $this->allowed_file_size = 10240; // 10 MB    
        $this->mimes = [
            'image'=>[ 'jpg'=> 'image/jpeg','jpeg'=> 'image/jpeg','png'=> 'image/png','webp'=>'image/webp'],
            'doc'=>[ 
                'doc' => 'application/msword',
                'docx'=> 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'xls' =>  'application/vnd.ms-excel',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'csv' => 'text/csv'
            ],
            'pdf'=>['pdf'=>'application/pdf']
        ];
                
       
        
    }
    
    public function getmimetype($_fn)
    {       
        $_rtnmime = $this->mimes[strtolower(pathinfo($_fn, PATHINFO_EXTENSION))];
        $mime = null;
        return $_rtnmime;
    }


    public function fileSecurityCheck($tmp_name,$mime_type,$extension,$ftype)
    {
       
        if($ftype == "image" )
        {                        
            if( !array_key_exists($extension,$this->mimes['image']) )
            {
                return ['status'=>false, 'msg'=>"File extenstion $extension is not accepted."];
            }            
            if( $this->mimes['image'][$extension] != $mime_type )
            {
                return ['status'=>false, 'msg'=>"Mime type not matched."];
            }
            $img = false;
            if( strtolower($extension) == "jpg" || strtolower($extension) == "jpeg" )
            {
                $img = imagecreatefromjpeg($tmp_name);
            }
            else if(strtolower($extension) == "png")
            {
                $img = imagecreatefrompng($tmp_name);
            }
            else if(strtolower($extension) == "webp")
            {
                $img = imagecreatefromwebp($tmp_name);
            }            
            $imgsize = getimagesize($tmp_name);
                
            if( !$img || !$imgsize){
                $res_err = "The image upload failed due to security restrictions that block certain invalid file.";
                return ['status'=>false, 'msg'=>$res_err];
            }
            return ['status'=>true];     
        }


        if( $ftype == "pdf" )
        {
            //print_r($this->mimes['pdf']); exit;
            if( !array_key_exists($extension,$this->mimes['pdf']) )
            {
                return ['status'=>false, 'msg'=>"File extenstion $extension is not accepted."];
            }            
            if( $this->mimes['pdf'][$extension] != $mime_type )
            {
                return ['status'=>false, 'msg'=>"Mime type not matched."];
            }
            $file_mime_type = finfo_file( finfo_open( FILEINFO_MIME_TYPE ),$tmp_name);
            if( $mime_type != $file_mime_type )
            {
                return ['status'=>false, 'msg'=>"Uploading failed due to security restrictions that block certain invalid files"];
            }
            return ['status'=>true];
        }
        if( $ftype == "doc" )
        {
            if( !array_key_exists($extension,$this->mimes['doc']) )
            {
                return ['status'=>false, 'msg'=>"File extenstion $extension is not accepted."];
            }            
            if( $this->mimes['doc'][$extension] != $mime_type )
            {
                return ['status'=>false, 'msg'=>"Mime type not matched."];
            }
            $file_mime_type = finfo_file( finfo_open( FILEINFO_MIME_TYPE ),$tmp_name);
            if( $mime_type != $file_mime_type )
            {
                return ['status'=>false, 'msg'=>"Uploading failed due to security restrictions that block certain invalid files"];
            }
            return ['status'=>true];
        }

    }

    public function uploadFiles($img_name, $file_new_name, $folder_path="docs")
    {                   
        global $config; 
        //echo '<pre>'; print_r($_FILES[$img_name]); echo '</pre>';
        $tmp_name = $_FILES[$img_name]['tmp_name'];
        $file_name = $_FILES[$img_name]['name']; 
        $mime_type = $_FILES[$img_name]['type'];
        $size = $_FILES[$img_name]['size'];

        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));                           
        $size_kb = round($size/1024);

        
        if (!isset($config['S3_bucket']) || !isset($config['S3Access_key']) || !isset($config['S3Scret_key']) || !isset($config['image_server'])) 
        {
            return ['status'=>false, 'msg'=>"Config fields are missed."];
        }

        if(!file_exists($tmp_name)) 
        {
            return ['status'=>false, 'msg'=>"Temp file missing"];
        }
        if($size_kb > $this->allowed_file_size) 
        {
            return ['status'=>false, 'msg'=>"File size must be less than 10 MB."];
        }

        $file_type = '';

        foreach($this->mimes as $ftype=>$val)
        {
            if(array_key_exists($ext,$val))
            {
                $file_type = $ftype; 
                break;
            }
        }
        
        if(empty($file_type)) 
        {
            return ['status'=>false, 'msg'=>"File extension $ext is not accepted."];
        }           
        if( $this->allowed_folders[$folder_path] )
        {
            $allowed_types = $this->allowed_folders[$folder_path];
            if( is_array($allowed_types) && !in_array($file_type,$allowed_types) )
            {
                return ['status'=>false, 'msg'=>"File not accepted, only ".implode(',',$allowed_types)."."];
            }
            else if( is_string($allowed_types) && $allowed_types != $file_type )
            {
                return ['status'=>false, 'msg'=>"File not accepted, only ".$allowed_types."."];
            } 
        }
        else{
            return ['status'=>false, 'msg'=>"Folder path not accepted."];
        }
       
        // Security check
        $check = $this->fileSecurityCheck($tmp_name, $mime_type, $ext, $file_type);
        if(!$check['status'])
        {
            return ['status'=>false, 'msg'=>$check['msg']];
        }
       
        $actual_file_name = date('Ym')."/".strtolower($file_new_name).".".$ext;
        $to = $folder_path."/".$actual_file_name;
        // Upload new file
        try {
            $response_obj = $this->s3_init->putObject([
                'Bucket'      => $this->s3_bucket,
                'Key'         => $to,
                'SourceFile'  => $tmp_name,
                'ACL'         => 'private',
                'ContentType' => $mime_type
            ]);                               
            if($response_obj['@metadata']['statusCode'] === 200) 
            {
                return ["status" => true,"file_name" => $actual_file_name];
            } 
            else 
            {
                return ["status" => false, "msg"=>"Failed to upload $file_name"];
            }            
        } 
        catch (Aws\S3\Exception\S3Exception $e) 
        {               
            return ["status" => false, "msg"=>"Failed to upload $file_name"];
        }
    }

    public static function imageLink($filename, $size = '')
    {
        global $config;
        if (empty($filename)) return '';

        // Define profile prefix based on environment
        $env = $config['env_server'] ?? 'dev';
        $prefix = ($env == "prod") ? "jlr" : (($env == "uat") ? "uat-jlr" : "stg-jlr");
        
        // Define thumbnail profiles
        $thumb_profiles = [
            "266x354"   =>  "$prefix-a",
            "285x380"   =>  "$prefix-b",
            "450x800"   =>  "$prefix-c",
            "600x800"   =>  "$prefix-d",
            "212x376"   =>  "$prefix-e",
            "255x255"   =>  "$prefix-g",
            "26x26"     =>  "$prefix-h",
            "333x500"   =>  "$prefix-i",
            "26x500"    =>  "$prefix-j",
            "225x300"   =>  "default",
        ];
        
        // Get profile or use default
        $default_profile = ($env == "prod") ? "jlr-d" : "default";
        $profile = $thumb_profiles[$size] ?? $default_profile;
        
        // Build and return image path
        return $config['image_server'] . "/thumbs/p-$profile-ver1/vimages/$filename";
    }
  
    // Download files from S3
    public function downloadFiles($base_folder,$destination_file_path,$local_save_path)
    {
       try
       {
          $destination_file_path = $base_folder."/".$destination_file_path;  
          $destination_file_path = str_replace("//", "/", $destination_file_path); 
          $errors = false;
          $errror_code = '';
          $response_obj = $this->s3_init->getObject([
              "Bucket" => $this->s3_bucket,
              "Key" => $destination_file_path,
              "SaveAs" => $local_save_path
          ]);
          if($response_obj['@metadata']['statusCode'] == 200 && $response_obj['@metadata']['effectiveUri'] != '')
          {
            return ["status" => 200,"data"=>$response_obj['@metadata']];
          }
       }
       catch(Aws\S3\Exception\S3Exception $e)
       {
	        $error = true;
	        $errorcode = $e->getstatusCode();
	        return ["status" => 500,"msg" => $e->getMessage()];
       }
    }

    // Delete file from S3
    public function deleteFile($url)
    {
        $file_url = $url ?? ''; 
        try
        {
            $error = false;
            $errorcode = '';
            $response_obj = $this->s3_init->deleteObject([
                'Bucket'=>$this->s3_bucket,
                'Key'=>$file_url
            ]);
            if($response_obj['@metadata']['statusCode'] == 204)
            {
                return ["status" => 200,"file_name" => $file_url];
            }
        }
        catch(Aws\S3\Exception\S3Exception $e)
        {
            $error = true;
            $errorcode = $e->getstatusCode();
            return ["status" => 500, "msg" => $e->getMessage()];
        }
    }

}
?>