<?php
/**
 *
 */    //共享文件下载
    public function download_for_ourbook(){
        $bid = $_GET['bid'];

        // halt(phpinfo());
        $bookone = M("f_books_yhfb_file")->where('id = '.$bid)->find();
        if ($bookone['commom_id']) {
          $result = M("f_books_yhfb_listfile")->where('fujianid = '.$bookone['commom_id'])->select();
          if ($result) {
            foreach ($result as $key => $value) {
               $files[] = '/web/project/frame_tpfive/shufang.com/public'.$value['fujian_url'];
            }
            $zipname =time().'.zip';
            $zip = new \ZipArchive();
            $res = $zip->open($zipname, \ZipArchive::CREATE);
            // $zip->addEmptyDir('newdir', \ZipArchive::CREATE);

            if ($res === TRUE) {
              foreach ($files as $file) {
            //这里直接用原文件的名字进行打包，也可以直接命名，需要注意如果文件名字一样会导致后面文件覆盖前面的文件，所以建议重新命名

               $new_filename = substr($file, strrpos($file, '/') + 1);
               $zip->addFile($file, $new_filename);
              }

               $zip->close();

	             ob_end_clean();


              header("Content-Type: application/force-download");//告诉浏览器强制下载
              header("Content-Transfer-Encoding: binary");
              header("Content-Type: application/zip");
              //说明：这里的filename生成下载后的文件名，可以进行优化，生成你自己想要的名字，后缀等等
              Header("Content-Disposition: attachment; filename=".basename($zipname));
              header("Content-Length: " . filesize($zipname));
	            error_reporting(0);
              readfile($zipname);
              flush();
              exit;
              }
            }else{
               echo "<a href='javascript:history.go(-1);'>该文件不存在，请点击此处返回上一页</a>";
           }
        }

        // $fileExists = @file_get_contents($bookone['f_books_yhfb_file_url']) ? true : false;
    }
