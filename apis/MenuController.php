<?php
namespace admin\apis;

use Yii;

/**
 * @author nadar
 */
class MenuController extends \admin\base\RestController
{
    private function getUserId()
    {
        return $this->getUser()->id;
    }
    
    private function getMenu()
    {
        return \luya\helpers\Param::get('adminMenus');
    }
    
    public function actionIndex()
    {
        $responseData = [];
        $index = 1;
        foreach ($this->getMenu() as $item) {
            $index++;
            // check if this is an entrie with a permission
            if ($item['permissionIsRoute']) {
                // verify if the permission is provided for this user:
                // if the permission is granted will write inti $responseData,
                // if not we continue;
                if (!Yii::$app->luya->auth->matchRoute($this->getUserId(), $item['permissionRoute'])) {
                    continue;
                }
            }

            // this item does have groups
            if (isset($item['groups'])) {

                $permissionGranted = false;

                // see if the groups has items
                foreach ($item['groups'] as $groupName => $groupItem) {
                    
                    if (count($groupItem['items'])  > 0) {

                        if ($permissionGranted) {
                            continue;
                        }
                        
                        foreach($groupItem['items'] as $groupItemEntry) {
                            // a previous entry already has solved the question if the permission is granted
                            if ($permissionGranted) {
                                continue;
                            }
                            if ($groupItemEntry['permissionIsRoute']) {
                                // when true, set permissionGranted to true
                                if (Yii::$app->luya->auth->matchRoute($this->getUserId(), $groupItemEntry['route'])) {
                                    $permissionGranted = true;
                                }
                            } elseif ($groupItemEntry['permissionIsApi']) {
                                // when true, set permissionGranted to true
                                if (Yii::$app->luya->auth->matchApi($this->getUserId(), $groupItemEntry['permssionApiEndpoint'])) {
                                    $permissionGranted = true;
                                }
                            } else {
                                throw new \Exception("Menu item detected without permission entry");
                            }
                        }
                    }
                }
                
                if (!$permissionGranted) {
                    continue;
                }
            }
            
            // ok we have passed all the tests, lets make an entry
            $responseData[] = [
                'id' => $index,
                'template' => $item['template'],
                'routing' => $item['routing'],
                'alias' => $item['alias'],
                'icon' => $item['icon'],
            ];
        }
        
        return $responseData;
        
    }   
    
    public function actionItems($nodeId)
    {
        $index = 1;
        foreach ($this->getMenu() as $item) {
            $index++;
            if ($nodeId == $index) {
                $data = $item;
                break;
            }
        }
        
        if (isset($data['groups'])) {
            foreach($data['groups'] as $groupName => $groupItem) {
                foreach($groupItem['items'] as $groupItemKey => $groupItemEntry) {
                    if ($groupItemEntry['permissionIsRoute']) {
                        // when true, set permissionGranted to true
                        if (!Yii::$app->luya->auth->matchRoute($this->getUserId(), $groupItemEntry['route'])) {
                            unset($data['groups'][$groupName]['items'][$groupItemKey]);
                        }
                    } elseif ($groupItemEntry['permissionIsApi']) {
                        // when true, set permissionGranted to true
                        if (!Yii::$app->luya->auth->matchApi($this->getUserId(), $groupItemEntry['permssionApiEndpoint'])) {
                            unset($data['groups'][$groupName]['items'][$groupItemKey]);
                        }
                    } else {
                        throw new \Exception("Menu itrem detected without permission entry");
                    }
                }
            }
        }
        
        return $data;
    }
}