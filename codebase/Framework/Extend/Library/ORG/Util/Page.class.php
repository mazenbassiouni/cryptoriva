<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: support@codono.com

// +----------------------------------------------------------------------

class Page
{

    // PagingEach column页to showPages
    public $rollPage = 5;
    // Pages Jump to bring the Parameters
    public $parameter;
    // Paging URL address
    public $url = '';
    // Each default list页The number of rows displayed
    public $listRows = 20;
    // Starting line number
    public $firstRow;
    // The total number of pages
    protected $totalPages;
    // Total number of rows
    protected $totalRows;
    // current page number
    protected $nowPage;
    // The total number of pages column of the page
    protected $coolPages;
    // Pagination custom
    protected $config = array('header' => 'Records', 'prev' => 'Previous', 'next' => 'Next', 'first' => 'First Page', 'last' => 'Last Page', 'theme' => ' %totalRow% %header% %nowPage%/%totalPage% pages %upPage% %downPage% %first%  %prePage%  %linkPage%  %nextPage% %end%');
    // defaultPaging variable name
    protected $varPage;

    /**
     * Architecture function
     * @access public
     * @param array $totalRows The total number of records
     * @param array $listRows The number of records per page
     * @param array $parameter Jump paging parameters
     */
    public function __construct($totalRows, $listRows = '', $parameter = '', $url = '')
    {
        $this->totalRows = $totalRows?:1;
        $this->parameter = $parameter;
        $this->varPage = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        if (!empty($listRows)) {
            $this->listRows = intval($listRows)?:1;
        }
        $this->totalPages = ceil($this->totalRows / $this->listRows);     //total pages
        $this->coolPages = ceil($this->totalPages / $this->rollPage);
        $this->nowPage = !empty($_GET[$this->varPage]) ? intval($_GET[$this->varPage]) : 1;
        if ($this->nowPage < 1) {
            $this->nowPage = 1;
        } elseif (!empty($this->totalPages) && $this->nowPage > $this->totalPages) {
            $this->nowPage = $this->totalPages;
        }
        $this->firstRow = $this->listRows * ($this->nowPage - 1);
        if (!empty($url)) $this->url = $url;
    }

    public function setConfig($name, $value)
    {
        if (isset($this->config[$name])) {
            $this->config[$name] = $value;
        }
    }

    /**
     * Pagination output
     * @access public
     */
    public function show()
    {
        if (0 == $this->totalRows) return '';
        $p = $this->varPage;
        $nowCoolPage = ceil($this->nowPage / $this->rollPage);

        // Analysis of paging parameters
        if ($this->url) {
            $depr = C('URL_PATHINFO_DEPR');
            $url = rtrim(U('/' . $this->url, '', false), $depr) . $depr . '__PAGE__';
        } else {
            if ($this->parameter && is_string($this->parameter)) {
                parse_str($this->parameter, $parameter);
            } elseif (is_array($this->parameter)) {
                $parameter = $this->parameter;
            } elseif (empty($this->parameter)) {
                unset($_GET[C('VAR_URL_PARAMS')]);
                $var = !empty($_POST) ? $_POST : $_GET;
                if (empty($var)) {
                    $parameter = array();
                } else {
                    $parameter = $var;
                }
            }
            $parameter[$p] = '__PAGE__';
            $url = U('', $parameter);
        }
        //Flip up and down the string
        $upRow = $this->nowPage - 1;
        $downRow = $this->nowPage + 1;
        if ($upRow > 0) {
            $upPage = "<a href='" . str_replace('__PAGE__', $upRow, $url) . "'>" . $this->config['prev'] . "</a>";
        } else {
            $upPage = '';
        }

        if ($downRow <= $this->totalPages) {
            $downPage = "<a href='" . str_replace('__PAGE__', $downRow, $url) . "'>" . $this->config['next'] . "</a>";
        } else {
            $downPage = '';
        }
        // << < > >>
        if ($nowCoolPage == 1) {
            $theFirst = '';
            $prePage = '';
        } else {
            $preRow = $this->nowPage - $this->rollPage;
            $prePage = "<a href='" . str_replace('__PAGE__', $preRow, $url) . "' >上" . $this->rollPage . "页</a>";
            $theFirst = "<a href='" . str_replace('__PAGE__', 1, $url) . "' >" . $this->config['first'] . "</a>";
        }
        if ($nowCoolPage == $this->coolPages) {
            $nextPage = '';
            $theEnd = '';
        } else {
            $nextRow = $this->nowPage + $this->rollPage;
            $theEndRow = $this->totalPages;
            $nextPage = "<a href='" . str_replace('__PAGE__', $nextRow, $url) . "' >下" . $this->rollPage . "页</a>";
            $theEnd = "<a href='" . str_replace('__PAGE__', $theEndRow, $url) . "' >" . $this->config['last'] . "</a>";
        }
        // 1 2 3 4 5
        $linkPage = "";
        for ($i = 1; $i <= $this->rollPage; $i++) {
            $page = ($nowCoolPage - 1) * $this->rollPage + $i;
            if ($page != $this->nowPage) {
                if ($page <= $this->totalPages) {
                    $linkPage .= "<a href='" . str_replace('__PAGE__', $page, $url) . "'>" . $page . "</a>";
                } else {
                    break;
                }
            } else {
                if ($this->totalPages != 1) {
                    $linkPage .= "<span class='current'>" . $page . "</span>";
                }
            }
        }
        $pageStr = str_replace(
            array('%header%', '%nowPage%', '%totalRow%', '%totalPage%', '%upPage%', '%downPage%', '%first%', '%prePage%', '%linkPage%', '%nextPage%', '%end%'),
            array($this->config['header'], $this->nowPage, $this->totalRows, $this->totalPages, $upPage, $downPage, $theFirst, $prePage, $linkPage, $nextPage, $theEnd), $this->config['theme']);
        return $pageStr;
    }

}
