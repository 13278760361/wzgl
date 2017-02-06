<?php
// +----------------------------------------------------------------------
// |  QY [ EASY TECH EASY LIFE ]
// +----------------------------------------------------------------------
// | 青才科技 (http://www.4000871428.com)
// +----------------------------------------------------------------------
// | Author: ronshn_cat (505221851@qq.com)
// +----------------------------------------------------------------------
// | Date: 2016-11-24 11:22:44
// +----------------------------------------------------------------------
namespace Admin\Controller; 
use Think\Controller;
/**
 * 物资基础信息处理
 */
class BarCodeController extends BaseController
{
    public function _initialize(){
      parent::_initialize();
     
     
    } 

  public function doCode($barCode){ //生成条形码 code 条形码生成规则
         vendor('Barcodegen.class.BCGFontFile');
         vendor('Barcodegen.class.BCGDrawing');
         vendor('Barcodegen.class.BCGColor');
         vendor('Barcodegen.class.BCGcode39');



          // Loading Font
          $font = new \BCGFontFile('./ThinkPHP/Library/Vendor/Barcodegen/font/Arial.ttf', 18);

        // Don't forget to sanitize user inputs

        // The arguments are R, G, B for color.
        $color_black = new \BCGColor(0, 0, 0);
        $color_white = new \BCGColor(255, 255, 255);

          $file_dir = 'Uploads/Bar/';
                /** 判断文件是否存在*/
                if(!file_exists($file_dir)) {
                    /** 不存在生成*/
                    mkdir($file_dir);
                }

        $drawException = null;
        try {
            $code = new \BCGcode39();
            $code->setScale(2); // Resolution
            $code->setThickness(30); // Thickness
            $code->setForegroundColor($color_black); // Color of bars
            $code->setBackgroundColor($color_white); // Color of spaces
            $code->setFont($font); // Font (or 0)
            $code->parse($barCode); // Text
        } catch(Exception $exception) {
            $drawException = $exception;
        }

      /* Here is the list of the arguments
      1 - Filename (empty : display on screen)
      2 - Background color */
      $drawing = new \BCGDrawing('', $color_white);
      if($drawException) {
          $drawing->drawException($drawException);
      } else {

          $drawing->setBarcode($code);

          /** 存放路径*/
          // echo $file_dir.$code;exit();
          $file_path = $file_dir.$barCode.time().'.png';

          // dump($file_path);exit();
          $drawing->setFilename($file_path);
         
          $drawing->draw();
      }

      // Header that says it is an image (remove it if you save the barcode to a file)
      // header('Content-Type: image/png');
      // header('Content-Disposition: inline; filename="barcode.png"');

      // Draw (or save) the image into PNG format.
      $drawing->finish(\BCGDrawing::IMG_FORMAT_PNG);

      return "/".$file_path;

  }
}
?>