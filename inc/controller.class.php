<?php

// The MIT License
// 
// Copyright (c) 2012 +THECHURCH+
// 
// Permission is hereby granted, free of charge, to any person obtaining a
// copy of this software and associated documentation files
// (the "Software"), to deal in the Software without restriction,
// including without limitation the rights to use, copy, modify, merge,
// publish, distribute, sublicense, and/or sell copies of the Software, and
// to permit persons to whom the Software is furnished to do so, subject to
// the following conditions:
// 
// The above copyright notice and this permission notice shall be included
// in all copies or substantial portions of the Software.
// 
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
// OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
// MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
// IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
// CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
// TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
// SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

require_once CSVAFPLUGINPATH . '/inc/model.class.php';
require_once CSVAFPLUGINPATH . '/inc/view.class.php';

require_once CSVAFPLUGINPATH . '/inc/PHPExcel/IOFactory.php';

/**
 * Constroller for the CSV Advanced Fields plugin.
 * 
 * @package Csvaf
 * @version 0.1
 */
class CsvafController {
  /**
   * Allowed file extensions for the file upload.
   *
   * @static
   * @access  public
   * @var     array
   */
  public static $ALLOWEDEXT = array();

  /**
   * Handle the incoming request.
   * 
   * @static
   * @access public
   * @return void
   */
  public static function Handle () {
    // Permissions
    if (!current_user_can('manage_options')) {
      return wp_die(
        __(
          'You do not have sufficient permissions to access this page.'
        , 'csvaf'
        )
      );
    }

    // Check state.
    // If we have a valid nonce then render out the next step.
    if (  is_string($_POST[CSVAFNONCEKEY])
       && wp_verify_nonce($_POST[CSVAFNONCEKEY], CSVAFNONCEKEY)
       ) {
      return self::Handleupload();
    }

    self::Uploadform();
  }

  /**
   * Render the upload form
   * 
   * @param string $before  The form head.
   * @param string $after   The form foot.
   * @static
   * @access public
   * @return void
   */
  protected static function Uploadform ($before = '', $after = '') {
    // nonce stuff
    $noncevalue = wp_create_nonce(CSVAFNONCEKEY);

    echo CsvafView::Uploadform('', CSVAFNONCEKEY, $noncevalue, $before, $after);
  }

  protected static function Handleupload () {
    // Check we have a single file that hasn't errored.
    if (  !is_array($_FILES['csvaf_data'])
       || is_array($_FILES['csvaf_data']['name']
       || UPLOAD_ERR_OK != $_FILES['csvaf_data']['error'])
       ) {
      $error = CsvafView::Errorbanner(
        __('Encountered a problem while trying to upload your file.', 'csvaf')
      );
      return self::Uploadform($error);
    }

    // Check if we allow the given file extension.
    // Don't bother checking mime type, as this is a admin page not for general
    // consumption.
    if (  !in_array(
            pathinfo($_FILES['csvaf_data']['name'], PATHINFO_EXTENSION)
          , self::$ALLOWEDEXT
          )
       ) {
      $error = CsvafView::Errorbanner(
        __(
            'File was not of the supported type. Please only upload Excel '
          . 'spreadsheets and CSV files.'
        , 'csvaf'
        )
      );
      return self::Uploadform($error);
    }

    // We have a file.
    // Lets give it to phpexcel to handle.
    $tmpname    = $_FILES['csvaf_data']['tmp_name'];
    $filename   = $tmpname . $_FILES['csvaf_data']['name'];
    move_uploaded_file($tmpname, $filename);

    echo pathinfo($filename, PATHINFO_EXTENSION);

    // $doc        = PHPExcel_IOFactory::load($filename);
    unlink($filename);

    // $doc_array  = $doc->getActiveSheet()->toArray(null, true, true, true);

    // TODO : Render out the options for linking spreadsheet data to page types
    // and fields.
    CsvafModel::Getposttypes();
    CsvafModel::Getfieldsfortype('vessel');
  }

  /**
   * Show our entry in the admin menu.
   * 
   * @static
   * @access public
   * @return void
   */
  public static function Adminmenu () {
    add_menu_page(
      'CSV Advanced Fields'
    , 'Upload CSV'
    , 'import'
    , 'upload-csv'
    , array('CsvafController', 'Handle')
    );
  }
}