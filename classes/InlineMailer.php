<?php

/*
error_reporting(E_ALL);
$paths = explode(PATH_SEPARATOR, get_include_path());
$paths[] = dirname(dirname(dirname(dirname(__FILE__)))) . '/library';
set_include_path(implode(PATH_SEPARATOR, $paths));

require_once 'Zend/Loader.php';
require_once 'Zend/Mail.php';
*/

class Demo_Zend_Mail_InlineImages extends Zend_Mail
{
    public function buildHtml()
    {
        // Important, without this line the example don't work!
        // The images will be attached to the email but these will be not
        // showed inline
        $this->setType(Zend_Mime::MULTIPART_RELATED);

        $matches = array();
        //preg_match_all("#<img.*?src=['\"]file://([^'\"]+)#i",
                      // $this->getBodyHtml(true),
                      // $matches);
                       
        preg_match_all("#file://([^'\"]+)#i",
                       $this->getBodyHtml(true),
                       $matches);
                       
        $matches = array_unique($matches[1]);

        if (count($matches ) > 0) {
            foreach ($matches as $key => $filename) {
                if (is_readable($filename)) {
                    $at = $this->createAttachment(file_get_contents($filename));
                    $at->type = $this->mimeByExtension($filename);
                    $at->disposition = Zend_Mime::DISPOSITION_INLINE;
                    $at->encoding = Zend_Mime::ENCODING_BASE64;
                    $at->id = 'cid_' . md5_file($filename);
                    $this->setBodyHtml(str_replace('file://' . $filename,
                                       'cid:' . $at->id,
                                       $this->getBodyHtml(true)),
                                       'UTF-8',
                                       Zend_Mime::ENCODING_8BIT);
                }
            }
        }
    }

    public function mimeByExtension($filename)
    {
        if (is_readable($filename) ) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            switch ($extension) {
                case 'gif':
                    $type = 'image/gif';
                    break;
                case 'jpg':
                case 'jpeg':
                    $type = 'image/jpg';
                    break;
                case 'png':
                    $type = 'image/png';
                    break;
                default:
                    $type = 'application/octet-stream';
            }
      }

      return $type;
  }

}//class


?>