<?php

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class excel_styles {
    private $sheetPIVOTSAC;
    private $sheetPIVOTEngineers;
    private $sheetMasterData;

    public function __construct(...$spreadsheet) {
        $this->sheetPIVOTSAC = $spreadsheet[0];
        $this->sheetPIVOTEngineers = $spreadsheet[1];
        $this->sheetMasterData = $spreadsheet[2];
    }

    public function incrementColumn($col) {
        $colIndex = Coordinate::columnIndexFromString($col);
        $colIndex++;
        return Coordinate::stringFromColumnIndex($colIndex);
    }

    public function getIntIndexCol($col) {
        // Convert a string column (e.g., "A") to an integer (e.g., 1)
        $colInt = Coordinate::columnIndexFromString($col);
        return $colInt;
    }
    
    public function getIndexCol($colInt) {
        // Convert an integer (e.g., 10) to a string column (e.g., "J")
        $colIndex = Coordinate::stringFromColumnIndex($colInt);
        return $colIndex;
    }

    public function setHeader($col, $row, $value, $bold, $bgColor, $fontFamily, $fontSize, $colWidth) {
        $this->sheetMasterData->setCellValue($col . $row, $value);
        $this->sheetMasterData->getStyle($col . $row)->getFont()->setColor(new Color(Color::COLOR_WHITE));
        $this->sheetMasterData->getStyle($col . $row)->getFont()->setName($fontFamily);
        $this->sheetMasterData->getStyle($col . $row)->getFont()->setSize($fontSize);
        $this->sheetMasterData->getStyle($col . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($bgColor);
        $this->sheetMasterData->getColumnDimension($col)->setWidth($colWidth);
        $this->sheetMasterData->getStyle($col . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $this->sheetMasterData->getStyle($col . $row)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $this->sheetMasterData->getStyle($col . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        if ($bold == true) {
            $this->sheetMasterData->getStyle($col . $row)->getFont()->setBold(true);
        }
    }

    public function setRowData($col, $row, $value, $fontSize, $fontFamily, $bold, $center) {
        $this->sheetMasterData->setCellValue($col . $row, $value);
        $this->sheetMasterData->getStyle($col . $row)->getFont()->setSize($fontSize);
        $this->sheetMasterData->getStyle($col . $row)->getFont()->setName($fontFamily);
        if ($bold == true) {
            $this->sheetMasterData->getStyle($col . $row)->getFont()->setBold(true);
        }
        if ($center === true) {
            $this->sheetMasterData->getStyle($col . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
        $this->sheetMasterData->getStyle($col . $row)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $this->sheetMasterData->getStyle($col . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    }

    public function setPivotHeaderEngineers($col, $row, $value, $bold, $bgColor, $fontFamily, $fontSize, $colWidth) {
        $this->sheetPIVOTEngineers->setCellValue($col . $row, $value);
        $this->sheetPIVOTEngineers->getStyle($col . $row)->getFont()->setColor(new Color(Color::COLOR_WHITE));
        $this->sheetPIVOTEngineers->getStyle($col . $row)->getFont()->setName($fontFamily);
        $this->sheetPIVOTEngineers->getStyle($col . $row)->getFont()->setSize($fontSize);
        $this->sheetPIVOTEngineers->getStyle($col . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($bgColor);
        $this->sheetPIVOTEngineers->getColumnDimension($col)->setWidth($colWidth);
        $this->sheetPIVOTEngineers->getStyle($col . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $this->sheetPIVOTEngineers->getStyle($col . $row)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $this->sheetPIVOTEngineers->getStyle($col . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        if ($bold == true) {
            $this->sheetPIVOTEngineers->getStyle($col . $row)->getFont()->setBold(true);
        }
    }

    public function setPivotDataEngineers($col, $row, $value, $fontSize, $fontFamily, $bold, $center) {
        $this->sheetPIVOTEngineers->setCellValue($col . $row, $value);
        $this->sheetPIVOTEngineers->getStyle($col . $row)->getFont()->setSize($fontSize);
        $this->sheetPIVOTEngineers->getStyle($col . $row)->getFont()->setName($fontFamily);
        if ($bold == true) {
            $this->sheetPIVOTEngineers->getStyle($col . $row)->getFont()->setBold(true);
        }
        if ($center == true) {
            $this->sheetPIVOTEngineers->getStyle($col . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $this->sheetPIVOTEngineers->getStyle($col . $row)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        }
        $this->sheetPIVOTEngineers->getStyle($col . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    }


    public function setPivotHeaderSAC($col, $row, $value, $bold, $bgColor, $fontFamily, $fontSize, $colWidth) {
        $this->sheetPIVOTSAC->setCellValue($col . $row, $value);
        $this->sheetPIVOTSAC->getStyle($col . $row)->getFont()->setColor(new Color(Color::COLOR_WHITE));
        $this->sheetPIVOTSAC->getStyle($col . $row)->getFont()->setName($fontFamily);
        $this->sheetPIVOTSAC->getStyle($col . $row)->getFont()->setSize($fontSize);
        $this->sheetPIVOTSAC->getStyle($col . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($bgColor);
        $this->sheetPIVOTSAC->getColumnDimension($col)->setWidth($colWidth);
        $this->sheetPIVOTSAC->getStyle($col . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $this->sheetPIVOTSAC->getStyle($col . $row)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $this->sheetPIVOTSAC->getStyle($col . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        if ($bold == true) {
            $this->sheetPIVOTSAC->getStyle($col . $row)->getFont()->setBold(true);
        }
    }

    public function setPivotDataSAC($col, $row, $value, $fontSize, $fontFamily, $bold, $center) {
        $this->sheetPIVOTSAC->setCellValue($col . $row, $value);
        $this->sheetPIVOTSAC->getStyle($col . $row)->getFont()->setSize($fontSize);
        $this->sheetPIVOTSAC->getStyle($col . $row)->getFont()->setName($fontFamily);
        if ($bold == true) {
            $this->sheetPIVOTSAC->getStyle($col . $row)->getFont()->setBold(true);
        }
        if ($center == true) {
            $this->sheetPIVOTSAC->getStyle($col . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $this->sheetPIVOTSAC->getStyle($col . $row)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        }
        $this->sheetPIVOTSAC->getStyle($col . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    }

    public function setAllBorders($sheet, $colRow) {
        $sheet->getStyle($colRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    }

}