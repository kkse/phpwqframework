<?php
/**
 * Created by PhpStorm.
 * User: kkse
 * Date: 2018/3/1
 * Time: 11:16
 */

namespace kkse\wqframework\base;


use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class Excel
{
    protected function column_str($key)
    {
        $array = array(
            'A',
            'B',
            'C',
            'D',
            'E',
            'F',
            'G',
            'H',
            'I',
            'J',
            'K',
            'L',
            'M',
            'N',
            'O',
            'P',
            'Q',
            'R',
            'S',
            'T',
            'U',
            'V',
            'W',
            'X',
            'Y',
            'Z',
            'AA',
            'AB',
            'AC',
            'AD',
            'AE',
            'AF',
            'AG',
            'AH',
            'AI',
            'AJ',
            'AK',
            'AL',
            'AM',
            'AN',
            'AO',
            'AP',
            'AQ',
            'AR',
            'AS',
            'AT',
            'AU',
            'AV',
            'AW',
            'AX',
            'AY',
            'AZ',
            'BA',
            'BB',
            'BC',
            'BD',
            'BE',
            'BF',
            'BG',
            'BH',
            'BI',
            'BJ',
            'BK',
            'BL',
            'BM',
            'BN',
            'BO',
            'BP',
            'BQ',
            'BR',
            'BS',
            'BT',
            'BU',
            'BV',
            'BW',
            'BX',
            'BY',
            'BZ'
        );
        return $array[$key];
    }
    protected function column($key, $columnnum = 1)
    {
        return $this->column_str($key) . $columnnum;
    }
    function export($list, $params = array())
    {
        if (PHP_SAPI == 'cli') {
            die('This example should only be run from a Web Browser');
        }
        set_time_limit(0);
//        require_once IA_ROOT . '/framework/library/phpexcel/PHPExcel.php';
        $excel = new Spreadsheet();
        $excel->getProperties()->setCreator("人人商城")->setLastModifiedBy("人人商城")->setTitle("Office 2007 XLSX Test Document")->setSubject("Office 2007 XLSX Test Document")->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")->setKeywords("office 2007 openxml php")->setCategory("report file");
        $sheet  = $excel->setActiveSheetIndex(0);
        $rownum = 1;
        foreach ($params['columns'] as $key => $column) {
            $sheet->setCellValue($this->column($key, $rownum), $column['title']);
            if (!empty($column['width'])) {
                $sheet->getColumnDimension($this->column_str($key))->setWidth($column['width']);
            }
        }
        $rownum++;
        foreach ($list as $row) {
            $len = count($row);
            for ($i = 0; $i < $len; $i++) {
                $value = $row[$params['columns'][$i]['field']];
                //$sheet->setCellValue($this->column($i, $rownum), $value);
                $sheet->getCell($this->column($i, $rownum))
                    ->setValueExplicit($value, DataType::TYPE_STRING);
            }
            $rownum++;
        }
        $excel->getActiveSheet()->setTitle($params['title']);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $params['title'] . '-' . date('Y-m-d H:i', time()) . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = IOFactory::createWriter($excel, 'Xlsx');
        $writer->save('php://output');
        exit;
    }
    public function import($excefile)
    {
        global $_W;
        set_time_limit(0);
        /*require_once IA_ROOT . '/framework/library/phpexcel/PHPExcel.php';
        require_once IA_ROOT . '/framework/library/phpexcel/PHPExcel/IOFactory.php';
        require_once IA_ROOT . '/framework/library/phpexcel/PHPExcel/Reader/Excel5.php';*/
        $path = IA_ROOT . "/addons/ewei_shop/data/tmp/";
        if (!is_dir($path)) {
            load()->func('file');
            mkdirs($path, '0777');
        }
        $file     = time() . $_W['uniacid'] . ".xlsx";
        $filename = $_FILES[$excefile]['name'];
        $tmpname  = $_FILES[$excefile]['tmp_name'];
        if (empty($tmpname)) {
            message('请选择要上传的Excel文件!', '', 'error');
        }
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if ($ext != 'xlsx') {
            message('请上传 xlsx 格式的Excel文件!', '', 'error');
        }
        $uploadfile = $path . $file;
        $result     = move_uploaded_file($tmpname, $uploadfile);
        if (!$result) {
            message('上传Excel 文件失败, 请重新上传!', '', 'error');
        }
        $reader             = IOFactory::createReader('Xlsx');
        $excel              = $reader->load($uploadfile);
        $sheet              = $excel->getActiveSheet();
        $highestRow         = $sheet->getHighestRow();
        $highestColumn      = $sheet->getHighestColumn();
        $highestColumnCount = Coordinate::columnIndexFromString($highestColumn);
        $values             = array();
        for ($row = 2; $row <= $highestRow; $row++) {
            $rowValue = array();
            for ($col = 0; $col < $highestColumnCount; $col++) {
                $rowValue[] = $sheet->getCellByColumnAndRow($col, $row)->getValue();
            }
            $values[] = $rowValue;
        }
        return $values;
    }
}