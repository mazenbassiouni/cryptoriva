<?php

namespace Admin\Controller;

class FileController extends AdminController
{
    public function upload()
    {

        $File = D('File');
        $file_driver = C('DOWNLOAD_UPLOAD_DRIVER');
        $info = $File->upload($_FILES, C('DOWNLOAD_UPLOAD'), C('DOWNLOAD_UPLOAD_DRIVER'), C('UPLOAD_' . $file_driver . '_CONFIG'));

        if ($info) {
            $return['data'] = think_encrypt(json_encode($info['download']));
            $return['info'] = $info['download']['name'];
            $return['status']=1;
        } else {
            $return['status'] = 0;
            $return['data']='';
            $return['info'] = $File->getError();
        }

        $this->ajaxReturn($return);
    }

    public function download($id = NULL)
    {
        if (empty($id) || !is_numeric($id)) {
            $this->error(L('INCORRECT_REQ'));
        }

        $logic = D('Download', 'Logic');

        if (!$logic->download($id)) {
            $this->error($logic->getError());
        }
    }

    public function uploadPicture()
    {
        $Picture = D('Picture');
        $pic_driver = C('PICTURE_UPLOAD_DRIVER');
        $info = $Picture->upload($_FILES, C('PICTURE_UPLOAD'), C('PICTURE_UPLOAD_DRIVER'), C('UPLOAD_' . $pic_driver . '_CONFIG'));

        if ($info) {
            $return['status'] = 1;
            $return['info'] = 'Upload successful';
            $return = array_merge($info['download'], $return);
        } else {
            $return['status'] = 0;
            $return['data']='';
            $return['info'] = $Picture->getError();
        }

        $this->ajaxReturn($return);
    }
}
