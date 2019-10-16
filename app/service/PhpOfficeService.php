<?php

declare(strict_types=1);

namespace app\service;

use think\Service;
use \PhpOffice\PhpSpreadsheet\Spreadsheet;
use \PhpOffice\PhpSpreadsheet\IOFactory;//use \PHPExcel_Style_NumberFormat;    //设置列的格式==>>设置文本格式

use \PhpOffice\PhpSpreadsheet\IOFactory;//导入类Import

class PhpOfficeService extends Service
{

    /**
     * 注册服务
     *
     * @return mixed
     */
    public function register()
    {
        //
    }


    /**
     * 执行服务
     *
     * @return mixed
     */
    public function boot()
    {
        //
    }

    public function exportExcel($startTime, $endTime)
    {
        $adminList = db('user')
            ->where('create_time', 'between', [$startTime, $endTime])
            ->order(['admin_id' => 'desc'])
            ->select();
        //return $adminList;

        $newExcel = new Spreadsheet();  //创建一个新的excel文档
        $objSheet = $newExcel->getActiveSheet();  //获取当前操作sheet的对象
        $objSheet->setTitle('管理员表');  //设置当前sheet的标题

        //设置宽度为true,不然太窄了
        $newExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
        $newExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
        $newExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
        $newExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);

        //设置第一栏的标题
        $objSheet->setCellValue('A1', 'id')
            ->setCellValue('B1', '用户名')
            ->setCellValue('C1', '密码')
            ->setCellValue('D1', '时间');

        //第二行起，每一行的值,setCellValueExplicit是用来导出文本格式的。
        //->setCellValueExplicit('C' . $k, $val['admin_password']PHPExcel_Cell_DataType::TYPE_STRING),可以用来导出数字不变格式
        foreach ($adminList as $k => $val) {
            $k = $k + 2;
            $objSheet->setCellValue('A' . $k, $val['admin_id'])
                ->setCellValue('B' . $k, $val['admin_username'])
                ->setCellValue('C' . $k, $val['admin_password'])
                ->setCellValue('D' . $k, date('Y-m-d H:i:s', $val['create_time']));
        }

        $this->downloadExcel($newExcel, '管理员表', 'Xls');
    }

    //公共文件，用来传入xls并下载
    function downloadExcel($newExcel, $filename, $format)
    {
        // $format只能为 Xlsx 或 Xls
        if ($format == 'Xlsx') {
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        } elseif ($format == 'Xls') {
            header('Content-Type: application/vnd.ms-excel');
        }

        header("Content-Disposition: attachment;filename="
            . $filename . date('Y-m-d') . '.' . strtolower($format));
        header('Cache-Control: max-age=0');
        $objWriter = IOFactory::createWriter($newExcel, $format);

        $objWriter->save('php://output');

        //通过php保存在本地的时候需要用到
        //$objWriter->save($dir.'/demo.xlsx');

        //以下为需要用到IE时候设置
        // If you're serving to IE 9, then the following may be needed
        //header('Cache-Control: max-age=1');
        // If you're serving to IE over SSL, then the following may be needed
        //header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        //header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        //header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        //header('Pragma: public'); // HTTP/1.0
        exit;
    }

    public function importExcel()
    {
        //获取表格的大小，限制上传表格的大小5M
        $file_size = $_FILES['myfile']['size'];
        if ($file_size > 5 * 1024 * 1024) {
            $this->error('文件大小不能超过5M');
            exit();
        }

        //限制上传表格类型
        $fileExtendName = substr(strrchr($_FILES['myfile']["name"], '.'), 1);
        //application/vnd.ms-excel  为xls文件类型
        if ($fileExtendName != 'xls') {
            $this->error('必须为excel表格，且必须为xls格式！');
            exit();
        }

        if (is_uploaded_file($_FILES['myfile']['tmp_name'])) {
            // 有Xls和Xlsx格式两种
            $objReader = IOFactory::createReader('Xls');

            $filename = $_FILES['myfile']['tmp_name'];
            $objPHPExcel = $objReader->load($filename);  //$filename可以是上传的表格，或者是指定的表格
            $sheet = $objPHPExcel->getSheet(0);   //excel中的第一张sheet
            $highestRow = $sheet->getHighestRow();       // 取得总行数
            // $highestColumn = $sheet->getHighestColumn();   // 取得总列数

            //定义$usersExits，循环表格的时候，找出已存在的用户。
            $usersExits = [];
            //循环读取excel表格，整合成数组。如果是不指定key的二维，就用$data[i][j]表示。
            for ($j = 2; $j <= $highestRow; $j++) {
                $data[$j - 2] = [
                    'admin_username' => $objPHPExcel->getActiveSheet()->getCell("A" . $j)->getValue(),
                    'admin_password' => $objPHPExcel->getActiveSheet()->getCell("B" . $j)->getValue(),
                    'create_time' => time()
                ];
                //看下用户名是否存在。将存在的用户名保存在数组里。
                $userExist = db('admin')->where('admin_username', $data[$j - 2]['admin_username'])->find();
                if ($userExist) {
                    array_push($usersExits, $data[$j - 2]['admin_username']);
                }
            }
            //halt($usersExits);

            //如果有已存在的用户名，就不插入数据库了。
            if ($usersExits != []) {
                //把数组变成字符串，向前端输出。
                $c = implode(" / ", $usersExits);
                $this->error('Excel中以下用户名已存在:' . $c, "/backend/admin/create", '', 20);
                exit();
            }

            //halt($data);
            //插入数据库
            $res = db('admin')->insertAll($data);
            if ($res) {
                $this->success('上传成功！', '/backend/admin', '', 1);
            }
        }
    }
}
