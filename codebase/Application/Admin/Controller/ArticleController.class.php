<?php

namespace Admin\Controller;

use Think\Page;
use Think\Upload;

class ArticleController extends AdminController
{
    public function index($name = NULL, $field = NULL, $status = NULL)
    {

        //$this->checkUpdata();
        $where = $this->sub_index($field, $name, $status);

        $count = M('Article')->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = M('Article')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['adminid'] = M('Admin')->where(array('id' => $v['adminid']))->getField('username');
            $list[$k]['type'] = M('ArticleType')->where(array('name' => $v['type']))->getField('title');
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }


    public function articleimage()
    {
        $upload = new Upload();
        $upload->maxSize = 3145728;
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
        $upload->rootPath = './Upload/article/';
        $upload->autoSub = false;
        $info = $upload->upload();

        foreach ($info as $k => $v) {
            $path = $v['savepath'] . $v['savename'];
            echo $path;
            exit();
        }
    }


    public function linkimage()
    {
        $upload = new Upload();
        $upload->maxSize = 3145728;
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
        $upload->rootPath = './Upload/link/';
        $upload->autoSub = false;
        $info = $upload->upload();

        foreach ($info as $k => $v) {
            $path = $v['savepath'] . $v['savename'];
            echo $path;
            exit();
        }
    }


    public function edit($id = NULL, $type = NULL)
    {

        if (empty($_POST)) {
            $list = M('ArticleType')->select();
            $listType = [];
            foreach ($list as $k => $v) {
                $listType[$v['name']] = $v['title'];
            }

            $this->assign('list', $listType);

            if ($id) {
                $this->data = M('Article')->where(array('id' => trim($id)))->find();
            } else {
                $this->data = null;
            }

            $this->display();
        } else {
            if (APP_DEMO) {
                $this->error(L('SYSTEM_IN_DEMO_MODE'));
            }

            if ($type == 'images') {
                $this->sub_uploadone();
            } else {
                $upload = new Upload();
                $upload->maxSize = 3145728;
                $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
                $upload->rootPath = './Upload/article/';
                $upload->autoSub = false;
                $info = $upload->upload();

                if ($info) {
                    foreach ($info as $k => $v) {
                        $_POST[$v['key']] = $v['savename'];
                    }
                }

                if ($_POST['addtime']) {
                    if (addtime(strtotime($_POST['addtime'])) == '---') {
                        //      $this->error('Added malformed');
                        $_POST['addtime'] = time();
                    } else {
                        $_POST['addtime'] = strtotime($_POST['addtime']);
                    }
                } else {
                    $_POST['addtime'] = time();
                }

                if ($_POST['endtime']) {
                    if (addtime(strtotime($_POST['endtime'])) == '---') {
                        //  $this->error('Edit the time format error');
                        $_POST['endtime'] = time();
                    } else {
                        $_POST['endtime'] = strtotime($_POST['endtime']);
                    }
                } else {
                    $_POST['endtime'] = time();
                }

                if ($_POST['id']) {
                    $rs = M('Article')->save($_POST);
                } else {
                    $_POST['addtime'] = time();
                    $_POST['adminid'] = session('admin_id');
                    $rs = M('Article')->add($_POST);
                }

                if ($rs) {
                    $this->success(L('SAVED_SUCCESSFULLY'));
                } else {
                    $this->error(L('COULD_NOT_SAVE'));
                }
            }
        }
    }

    public function typeStatus($id = NULL, $type = NULL, $model = 'ArticleType')
    {
        $this->status($id, $type, $model);
    }

    public function status($id = NULL, $type = NULL, $model = 'Article')
    {

        A('User')->sub_status($id, $type, $model);
    }

    public function type($name = NULL, $field = NULL, $status = NULL)
    {
        $where = $this->sub_index($field, $name, $status);

        $count = M('ArticleType')->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = M('ArticleType')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['adminid'] = M('Admin')->where(array('id' => $v['adminid']))->getField('username');
            $list[$k]['shang'] = M('ArticleType')->where(array('name' => $v['shang']))->getField('title');

            if (!$list[$k]['shang']) {
                $list[$k]['shang'] = 'Top';
            }
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function typeEdit($id = NULL, $type = NULL)
    {
        $list = M('ArticleType')->select();

        foreach ($list as $k => $v) {
            $listType[$v['name']] = $v['title'];
        }

        $this->assign('list', $listType);

        if (empty($_POST)) {
            if ($id) {
                $this->data = M('ArticleType')->where(array('id' => trim($id)))->find();
            } else {
                $this->data = null;
            }

            $this->display();
        } else {
            if (APP_DEMO) {
                $this->error(L('SYSTEM_IN_DEMO_MODE'));
            }

            if ($type == 'images') {
                $this->sub_uploadone();
            } else {
                if ($_POST['addtime']) {
                    if (addtime(strtotime($_POST['addtime'])) == '---') {
                        //$this->error('Added malformed');
                        $_POST['addtime'] = time();
                    } else {
                        $_POST['addtime'] = strtotime($_POST['addtime']);
                    }
                } else {
                    $_POST['addtime'] = time();
                }

                if ($_POST['endtime']) {
                    if (addtime(strtotime($_POST['endtime'])) == '---') {
                        //$this->error('Edit the time format error');
                        $_POST['edittime'] = time();
                    } else {
                        $_POST['endtime'] = strtotime($_POST['endtime']);
                    }
                } else {
                    $_POST['endtime'] = time();
                }

                if ($_POST['id']) {
                    $rs = M('ArticleType')->save($_POST);
                } else {
                    $_POST['adminid'] = session('admin_id');
                    $rs = M('ArticleType')->add($_POST);
                }

                if ($rs) {
                    $this->success(L('SAVED_SUCCESSFULLY'));
                } else {
                    $this->error(L('COULD_NOT_SAVE'));
                }
            }
        }
    }


    public function adver($name = NULL, $field = NULL, $status = NULL)
    {
        $where = $this->sub_index($field, $name, $status);

        $count = M('Adver')->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = M('Adver')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function adverEdit($id = NULL)
    {
        if (empty($_POST)) {
            if ($id) {
                $this->data = M('Adver')->where(array('id' => trim($id)))->find();
            } else {
                $this->data = null;
            }

            $this->display();
        } else {
            if (APP_DEMO) {
                $this->error(L('SYSTEM_IN_DEMO_MODE'));
            }


            $upload = new Upload();
            $upload->maxSize = 3145728;
            $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
            $upload->rootPath = './Upload/ad/';
            $upload->autoSub = false;
            $info = $upload->upload();


            if ($info) {
                foreach ($info as $k => $v) {
                    $_POST[$v['key']] = $v['savename'];
                }
            }

            $_POST['addtime'] = time();
            $_POST['endtime'] = time();

            if ($_POST['id']) {
                $rs = M('Adver')->save($_POST);
            } else {
                $_POST['adminid'] = session('admin_id');
                $rs = M('Adver')->add($_POST);
            }

            if ($rs) {
                $this->success(L('SAVED_SUCCESSFULLY'));
            } else {
                $this->error(L('COULD_NOT_SAVE'));
            }
        }
    }

    public function adverStatus($id = NULL, $type = NULL, $model = 'Adver')
    {
        A('User')->sub_status($id,$type,$model);
    }

    public function adverImage()
    {
        $upload = new Upload();
        $upload->maxSize = 3145728;
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
        $upload->rootPath = './Upload/ad/';
        $upload->autoSub = false;
        $info = $upload->upload();

        foreach ($info as $k => $v) {
            $path = $v['savepath'] . $v['savename'];
            echo $path;
            exit();
        }
    }

    public function link($name = NULL, $field = NULL, $status = NULL)
    {
        $where = $this->sub_index($field, $name, $status);

        $count = M('Link')->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = M('Link')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function linkEdit($id = NULL)
    {

        if (empty($_POST)) {
            if ($id) {
                $this->data = M('Link')->where(array('id' => trim($id)))->find();
            } else {
                $this->data = null;
            }

            $this->display();
        } else {
            if (APP_DEMO) {
                $this->error(L('SYSTEM_IN_DEMO_MODE'));
            }

            if ($_POST['addtime']) {
                if (addtime(strtotime($_POST['addtime'])) == '---') {
                    $this->error('Added malformed');
                } else {
                    $_POST['addtime'] = strtotime($_POST['addtime']);
                }
            } else {
                $_POST['addtime'] = time();
            }

            if ($_POST['endtime']) {
                if (addtime(strtotime($_POST['endtime'])) == '---') {
                    $this->error('Edit the time format error');
                } else {
                    $_POST['endtime'] = strtotime($_POST['endtime']);
                }
            } else {
                $_POST['endtime'] = time();
            }

            if ($_POST['id']) {
                $rs = M('Link')->save($_POST);
            } else {
                $rs = M('Link')->add($_POST);
            }

            if ($rs) {
                $this->success(L('SAVED_SUCCESSFULLY'));
            } else {
                $this->error(L('COULD_NOT_SAVE'));
            }
        }
    }

    public function linkStatus($id = NULL, $type = NULL, $model = 'Link')
    {
        A('User')->sub_status($id,$type,$model);
    }

    public function checkUpdata()
    {
        if (!S(MODULE_NAME . CONTROLLER_NAME . 'checkUpdata')) {
            $list = M('Menu')->where(array(
                'url' => 'Article/index',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else if (!$list) {
                M('Menu')->add(array('url' => 'Article/index', 'title' => 'Article Manager', 'pid' => 2, 'sort' => 1, 'hide' => 0, 'group' => 'content', 'ico_name' => 'list-alt'));
            } else {
                M('Menu')->where(array(
                    'url' => 'Article/index',
                    'pid' => array('neq', 0)
                ))->save(array('title' => 'Article Manager', 'pid' => 2, 'sort' => 1, 'hide' => 0, 'group' => 'content', 'ico_name' => 'list-alt'));
            }

            $list = M('Menu')->where(array(
                'url' => 'Article/type',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else if (!$list) {
                M('Menu')->add(array('url' => 'Article/type', 'title' => 'Article Type', 'pid' => 2, 'sort' => 2, 'hide' => 0, 'group' => 'content', 'ico_name' => 'list-alt'));
            } else {
                M('Menu')->where(array(
                    'url' => 'Article/type',
                    'pid' => array('neq', 0)
                ))->save(array('title' => 'Article Type', 'pid' => 2, 'sort' => 2, 'hide' => 0, 'group' => 'content', 'ico_name' => 'list-alt'));
            }

            $list = M('Menu')->where(array(
                'url' => 'Article/adver',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else if (!$list) {
                M('Menu')->add(array('url' => 'Article/adver', 'title' => 'Advertising management', 'pid' => 2, 'sort' => 3, 'hide' => 0, 'group' => 'content', 'ico_name' => 'list-alt'));
            } else {
                M('Menu')->where(array(
                    'url' => 'Article/adver',
                    'pid' => array('neq', 0)
                ))->save(array('title' => 'Advertising management', 'pid' => 2, 'sort' => 3, 'hide' => 0, 'group' => 'content', 'ico_name' => 'list-alt'));
            }

            $list = M('Menu')->where(array(
                'url' => 'Article/link',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else if (!$list) {
                M('Menu')->add(array('url' => 'Article/link', 'title' => 'Links', 'pid' => 2, 'sort' => 4, 'hide' => 0, 'group' => 'content', 'ico_name' => 'list-alt'));
            } else {
                M('Menu')->where(array(
                    'url' => 'Article/link',
                    'pid' => array('neq', 0)
                ))->save(array('title' => 'Links', 'pid' => 2, 'sort' => 4, 'hide' => 0, 'group' => 'content', 'ico_name' => 'list-alt'));
            }

            $list = M('Menu')->where(array(
                'url' => 'Article/status',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else {
                $pid = M('Menu')->where(array(
                    'url' => 'Article/index',
                    'pid' => array('neq', 0)
                ))->getField('id');

                if (!$list) {
                    M('Menu')->add(array('url' => 'Article/status', 'title' => 'Modify status', 'pid' => $pid, 'sort' => 100, 'hide' => 1, 'group' => 'content', 'ico_name' => 'home'));
                } else {
                    M('Menu')->where(array(
                        'url' => 'Article/status',
                        'pid' => array('neq', 0)
                    ))->save(array('title' => 'Modify status', 'pid' => $pid, 'sort' => 100, 'hide' => 1, 'group' => 'content', 'ico_name' => 'home'));
                }
            }

            $list = M('Menu')->where(array(
                'url' => 'Article/edit',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else {
                $pid = M('Menu')->where(array(
                    'url' => 'Article/index',
                    'pid' => array('neq', 0)
                ))->getField('id');

                if (!$list) {
                    M('Menu')->add(array('url' => 'Article/edit', 'title' => 'Edit Add', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'content', 'ico_name' => 'home'));
                } else {
                    M('Menu')->where(array(
                        'url' => 'Article/edit',
                        'pid' => array('neq', 0)
                    ))->save(array('title' => 'Edit Add', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'content', 'ico_name' => 'home'));
                }
            }

            $list = M('Menu')->where(array(
                'url' => 'Article/typeEdit',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else {
                $pid = M('Menu')->where(array(
                    'url' => 'Article/type',
                    'pid' => array('neq', 0)
                ))->getField('id');

                if (!$list) {
                    M('Menu')->add(array('url' => 'Article/typeEdit', 'title' => 'Edit Add', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'content', 'ico_name' => 'home'));
                } else {
                    M('Menu')->where(array(
                        'url' => 'Article/typeEdit',
                        'pid' => array('neq', 0)
                    ))->save(array('title' => 'Edit Add', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'content', 'ico_name' => 'home'));
                }
            }

            $list = M('Menu')->where(array(
                'url' => 'Article/typeStatus',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else {
                $pid = M('Menu')->where(array(
                    'url' => 'Article/type',
                    'pid' => array('neq', 0)
                ))->getField('id');

                if (!$list) {
                    M('Menu')->add(array('url' => 'Article/typeStatus', 'title' => 'Modify status', 'pid' => $pid, 'sort' => 100, 'hide' => 1, 'group' => 'content', 'ico_name' => 'home'));
                } else {
                    M('Menu')->where(array(
                        'url' => 'Article/typeStatus',
                        'pid' => array('neq', 0)
                    ))->save(array('title' => 'Modify status', 'pid' => $pid, 'sort' => 100, 'hide' => 1, 'group' => 'content', 'ico_name' => 'home'));
                }
            }

            $list = M('Menu')->where(array(
                'url' => 'Article/linkEdit',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else {
                $pid = M('Menu')->where(array(
                    'url' => 'Article/link',
                    'pid' => array('neq', 0)
                ))->getField('id');

                if (!$list) {
                    M('Menu')->add(array('url' => 'Article/linkEdit', 'title' => 'Edit Add', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'content', 'ico_name' => 'home'));
                } else {
                    M('Menu')->where(array(
                        'url' => 'Article/linkEdit',
                        'pid' => array('neq', 0)
                    ))->save(array('title' => 'Edit Add', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'content', 'ico_name' => 'home'));
                }
            }

            $list = M('Menu')->where(array(
                'url' => 'Article/linkStatus',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else {
                $pid = M('Menu')->where(array(
                    'url' => 'Article/link',
                    'pid' => array('neq', 0)
                ))->getField('id');

                if (!$list) {
                    M('Menu')->add(array('url' => 'Article/linkStatus', 'title' => 'Modify status', 'pid' => $pid, 'sort' => 100, 'hide' => 1, 'group' => 'content', 'ico_name' => 'home'));
                } else {
                    M('Menu')->where(array(
                        'url' => 'Article/linkStatus',
                        'pid' => array('neq', 0)
                    ))->save(array('title' => 'Modify status', 'pid' => $pid, 'sort' => 100, 'hide' => 1, 'group' => 'content', 'ico_name' => 'home'));
                }
            }

            $list = M('Menu')->where(array(
                'url' => 'Article/adverEdit',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else {
                $pid = M('Menu')->where(array(
                    'url' => 'Article/adver',
                    'pid' => array('neq', 0)
                ))->getField('id');

                if (!$list) {
                    M('Menu')->add(array('url' => 'Article/adverEdit', 'title' => 'Edit Add', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'content', 'ico_name' => 'home'));
                } else {
                    M('Menu')->where(array(
                        'url' => 'Article/adverEdit',
                        'pid' => array('neq', 0)
                    ))->save(array('title' => 'Edit Add', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'content', 'ico_name' => 'home'));
                }
            }

            $list = M('Menu')->where(array(
                'url' => 'Article/adverStatus',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else {
                $pid = M('Menu')->where(array(
                    'url' => 'Article/adver',
                    'pid' => array('neq', 0)
                ))->getField('id');

                if (!$list) {
                    M('Menu')->add(array('url' => 'Article/adverStatus', 'title' => 'Modify status', 'pid' => $pid, 'sort' => 100, 'hide' => 1, 'group' => 'content', 'ico_name' => 'home'));
                } else {
                    M('Menu')->where(array(
                        'url' => 'Article/adverStatus',
                        'pid' => array('neq', 0)
                    ))->save(array('title' => 'Modify status', 'pid' => $pid, 'sort' => 100, 'hide' => 1, 'group' => 'content', 'ico_name' => 'home'));
                }
            }

            $list = M('Menu')->where(array(
                'url' => 'Article/adverImage',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else {
                $pid = M('Menu')->where(array(
                    'url' => 'Article/adver',
                    'pid' => array('neq', 0)
                ))->getField('id');

                if (!$list) {
                    M('Menu')->add(array('url' => 'Article/adverImage', 'title' => 'upload image', 'pid' => $pid, 'sort' => 100, 'hide' => 1, 'group' => 'content', 'ico_name' => 'home'));
                } else {
                    M('Menu')->where(array(
                        'url' => 'Article/adverImage',
                        'pid' => array('neq', 0)
                    ))->save(array('title' => 'upload image', 'pid' => $pid, 'sort' => 100, 'hide' => 1, 'group' => 'content', 'ico_name' => 'home'));
                }
            }

            if (M('Menu')->where(array('url' => 'Adver/index'))->delete()) {
                M('AuthRule')->where(array('status' => 1))->delete();
            }

            if (M('Menu')->where(array('url' => 'Link/index'))->delete()) {
                M('AuthRule')->where(array('status' => 1))->delete();
            }

            if (M('Menu')->where(array('url' => 'Articletype/index'))->delete()) {
                M('AuthRule')->where(array('status' => 1))->delete();
            }

            if (M('Menu')->where(array('url' => 'Chat/index'))->delete()) {
                M('AuthRule')->where(array('status' => 1))->delete();
            }

            $DbFields = M('Article')->getDbFields();

            if (!in_array('footer', $DbFields)) {
                M()->execute('ALTER TABLE `codono_article` ADD COLUMN `footer` VARCHAR(200)  NOT NULL   COMMENT \' \' AFTER `id`;');
            }

            if (!in_array('index', $DbFields)) {
                M()->execute('ALTER TABLE `codono_article` ADD COLUMN `index` VARCHAR(200)  NOT NULL   COMMENT \' \' AFTER `id`;');
            }

            $DbFields = M('ArticleType')->getDbFields();

            if (!in_array('footer', $DbFields)) {
                M()->execute('ALTER TABLE `codono_article_type` ADD COLUMN `footer` VARCHAR(200)  NOT NULL   COMMENT \' \' AFTER `id`;');
            }

            if (!in_array('index', $DbFields)) {
                M()->execute('ALTER TABLE `codono_article_type` ADD COLUMN `index` VARCHAR(200)  NOT NULL   COMMENT \' \' AFTER `id`;');
            }

            if (!in_array('content', $DbFields)) {
                M()->execute('ALTER TABLE `codono_article_type` ADD COLUMN `content` TEXT NOT NULL    COMMENT \' \' AFTER `id`;');
            }

            if (!in_array('shang', $DbFields)) {
                M()->execute('ALTER TABLE `codono_article_type` ADD COLUMN `shang` TEXT NOT NULL    COMMENT \' \' AFTER `id`;');
            }

            S(MODULE_NAME . CONTROLLER_NAME . 'checkUpdata', 1);
        }
    }

    /**
     * @param $field
     * @param $name
     * @param $status
     * @return array
     */
    private function sub_index($field, $name, $status): array
    {
        $where = array();

        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = M('User')->where(array('username' => $name))->getField('id');
            } else if ($field == 'title') {
                $where['title'] = array('like', '%' . $name . '%');
            } else {
                $where[$field] = $name;
            }
        }

        if ($status) {
            $where['status'] = $status - 1;
        }
        return $where;
    }

    /**
     * @return void
     */
    private function sub_uploadone(): void
    {
        $baseUrl = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        $upload = new Upload();
        $upload->maxSize = 3145728;
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
        $upload->rootPath = './Upload/article/';
        $upload->autoSub = false;
        $info = $upload->uploadOne($_FILES['imgFile']);

        if ($info) {
            $data = array('url' => str_replace('./', '/', $upload->rootPath) . $info['savename'], 'error' => 0);
            exit(json_encode($data));
        } else {
            $error['error'] = 1;
            $error['message'] = '';
            exit(json_encode($error));
        }
    }
}