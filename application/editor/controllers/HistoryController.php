<?php
class Editor_HistoryController extends OpenSKOS_Controller_Editor
{
    public function indexAction()
    {
        $user =  OpenSKOS_Db_Table_Users::fromIdentity();
        $history = $user->getUserHistory();
        $data = array();
        foreach ($history as $concept) {
            $data[] = $concept->toArray(array('uuid', 'uri', 'status', 'schemes', 'previewLabel', 'previewScopeNote'));
        }
        if (!empty($data)) {
            $this->getHelper('json')->sendJson(array(
                'status' => 'ok',
                'result' => $data));
        } else {
            $this->getHelper('json')->sendJson(array(
                    'status' => 'ok',
                    'result' => ''));
        }
    }
    public function clearHistoryAction()
    {
        $user =  OpenSKOS_Db_Table_Users::fromIdentity();
        if (null !== $user) {
            $user->clearUserHistory();
        }
        $this->getHelper('json')->sendJson(array('status' =>'ok'));
    }
}
