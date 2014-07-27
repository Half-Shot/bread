<?php

/*
 * The MIT License
 *
 * Copyright 2014 will.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * Description of BreadTableElement
 *
 * @author will
 */
namespace Bread\Structures;
include_once 'BreadThemeElement.php';
class BreadTableElement extends BreadThemeElement {
    /**
     * @var Bread\Structures\BreadTableRow
     */
    public $headingRow;
    /**
     * @var array of Bread\Structures\BreadRow
     */
    public $rows = array();
}

class BreadTableRow extends BreadThemeElement {    
    /**
     * @var array of Bread\Structures\BreadCell
     */
    public $cells = array();
    /**
     * Fill out the row quickly with an array of each cell's body.
     * @param array $Rows Strings for each cell.
     */
    function FillOutRow($CellData,$CellTemplate = false){
        foreach($CellData as $Body){
            if($CellTemplate == false){
                $Cell = new BreadTableCell();
            }
            else{
                $Cell = clone $CellTemplate;
            }
            $Cell->text = $Body;
            $this->cells[] = $Cell;
        }
    }
}

class BreadTableCell extends BreadThemeElement {
    public $text = "";
    public $width = "auto";
}