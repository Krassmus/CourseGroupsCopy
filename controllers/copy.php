<?php

require_once('app/controllers/plugin_controller.php');

class CopyController extends PluginController
{
    public function select_course_action()
    {

    }

    public function to_course_action()
    {
        $course_id = $_SESSION['SessionSeminar'] ?: Context::get()->id;
        $new_course_id = Request::option("seminar_id");
        if (Request::isPost()
                && $GLOBALS['perm']->have_studip_perm("tutor", $course_id)
                && $GLOBALS['perm']->have_studip_perm("tutor", $new_course_id)) {

            foreach (Request::getArray("gruppen") as $statusgruppe_id) {
                $statusgruppe = Statusgruppen::find($statusgruppe_id);
                if ($statusgruppe['range_id'] === $course_id) {
                    $new_group = new Statusgruppen();
                    $new_group->setData($statusgruppe->toArray());
                    $new_group['chdate'] = time();
                    $new_group['mkdate'] = time();
                    $new_group->setId($new_group->getNewId());
                    $new_group['range_id'] = $new_course_id;
                    $new_group->store();

                    //Dateiordner?
                    $statement = DBManager::get()->prepare("
                        SELECT * 
                        FROM folder
                        WHERE range_id = ?
                    ");
                    $statement->execute(array($statusgruppe->getId()));
                    $folder_data = $statement->fetch(PDO::FETCH_ASSOC);
                    if ($folder_data) {
                        $statement = DBManager::get()->prepare("
                            INSERT IGNORE INTO folder
                            SET folder_id = :folder_id,
                                range_id = :range_id,
                                seminar_id = :seminar_id,
                                user_id = :user_id,
                                `name` = :name,
                                description = :description,
                                mkdate = UNIX_TIMESTAMP(),
                                chdate = UNIX_TIMESTAMP(),
                                priority = :priority
                        ");
                        $statement->execute(array(
                            'folder_id' => md5(uniqid()),
                            'range_id' => $new_group->getId(),
                            'seminar_id' => $new_course_id,
                            'user_id' => $GLOBALS['user']->id,
                            'name' => $folder_data['name'],
                            'description' => $folder_data['description'],
                            'priority' => $folder_data['priority']
                        ));
                    }
                }
            }
        }
        PageLayout::postMessage(MessageBox::success(_("Gruppen wurden kopiert.")));
        $this->redirect(URLHelper::getURL("admin_statusgruppe.php", array('cid' => $new_course_id)));
    }
}
