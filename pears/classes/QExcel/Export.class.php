<?php

require_once DIR_ROOT ."/vendor/autoload.php";

/*
|--------------------------------------------------------------------------
| QExcel Export
|--------------------------------------------------------------------------
|
|   $title = 'this is title';
|    $cellTitle = [
|        'sheet1' => [
|            'username111' => '用户名111',
|            'name111' => '姓名111',
|        ],
|        'sheet2' => [
|            'username222' => '用户名222',
|            'name222' => '姓名222',
|        ],
|    ];
|    $sourceData = [
|        'sheet1' => [
|            [
|                'username111' => 'test.shi',
|                'name111' => 'test',
|            ],
|            [
|                'username111' => 'test.hao',
|                'name111' => 'testx',
|            ],
|        ],
|        'sheet2' => [
|            [
|                'username222' => 'test.shi',
|                'name222' => 'test',
|            ],
|            [
|                'username222' => 'test.hao',
|                'name222' => 'testx',
|            ],
|        ],
|    ];
|
*/

class QExcel_Export
{
    /**
     * phpExcel instance
     * @var PHPExcel $phpExcelInstance
     */
    protected $phpExcelInstance;

    /**
     * 横向单元格标识
     * @var array $cellName
     */
    protected $cellName = [
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
        'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ',
        'BA', 'BB', 'BC', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BK', 'BL', 'BM', 'BN', 'BO', 'BP', 'BQ', 'BR', 'BS', 'BT', 'BU', 'BV', 'BW', 'BX', 'BY', 'BZ',
        'CA', 'CB', 'CC', 'CD', 'CE', 'CF', 'CG', 'CH', 'CI', 'CJ', 'CK', 'CL', 'CM', 'CN', 'CO', 'CP', 'CQ', 'CR', 'CS', 'CT', 'CU', 'CV', 'CW', 'CX', 'CY', 'CZ',
    ];

    /**
     * title
     * @var string $title
     */
    protected $title = '';

    /**
     * cell title
     * @var array $cellTitle
     */
    protected $cellTitle = [];

    /**
     * source data
     * @var array $cellTitle
     */
    protected $sourceData = [];

    public function __construct($title, $cellTitle, $sourceData)
    {
        $this->title = $title;
        $this->cellTitle = $cellTitle;
        $this->sourceData = $sourceData;
        $this->setPhpExcelInstance();
        $this->seedExcelData();

    }

    public function seedExcelData()
    {
        $sheetIndex = 0;
        foreach ($this->sourceData as $sheetName => $data){
            if ($sheetIndex > 0){
                $this->phpExcelInstance->createSheet();
            }
            // select sheet
            $this->phpExcelInstance->setActiveSheetIndex($sheetIndex);

            if ((string) $sheetName != (string) $sheetIndex) {
                //设置当前活动sheet的名称
                $this->phpExcelInstance->getActiveSheet()->setTitle($sheetName);
            }

            if (count($data) > 0) {
                $cellKeys = array_keys($data[0]);
                $cellColumns = [];
                foreach ($cellKeys as $cellNum => $currentKey) {
                    $this->phpExcelInstance->getActiveSheet()->getColumnDimension($this->cellName[$cellNum])->setWidth(20);
                    $cellColumns[$currentKey] = isset($this->cellTitle[$sheetName][$currentKey])
                        ? $this->cellTitle[$sheetName][$currentKey]
                        : (
                        isset($this->cellTitle[$currentKey])
                            ? $this->cellTitle[$currentKey]
                            : ''
                        );
                }
                array_unshift($data, $cellColumns);
                $data = array_values($data);
                foreach ($data as $rowNum => $item) {
                    $item = array_values($item);
                    foreach ($item as $cellNum => $row) {
                        $currentCellIndex = $this->cellName[$cellNum] . ($rowNum + 1);
                        if ($rowNum == 0) {
                            $this->phpExcelInstance->getActiveSheet()->getStyle($currentCellIndex)->getFont()->setSize(16);
                            $this->phpExcelInstance->getActiveSheet()->getStyle($currentCellIndex)->applyFromArray([
                                'fill' => [
                                    'type' => PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                    'color' => ['rgb' => '88D44E']
                                ]
                            ]);
                        }
                        $this->phpExcelInstance->getActiveSheet()->setCellValue($currentCellIndex, $row);
                    }
                }
            }

            $sheetIndex++;
        }
    }

    /**
     * set phpExcel instance
     */
    private function setPhpExcelInstance()
    {
        $this->phpExcelInstance = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    }

    public function export()
    {
        $outputFileName = $this->title . ".xls";
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header('Content-Disposition:inline;filename="'.$outputFileName.'"');
        header("Content-Transfer-Encoding: binary");
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");

        $objWriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($this->phpExcelInstance, 'Xls');
        $objWriter->save('php://output');
    }
}
















