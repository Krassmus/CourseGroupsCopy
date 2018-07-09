<?php

require_once('app/controllers/plugin_controller.php');

class CopyController extends PluginController
{
    public function select_course_action()
    {
        $this->search = new SQLSearch(
            "
            SELECT seminare.Seminar_id, seminare.Name
            FROM seminare
                LEFT JOIN semester_data ON (seminare.start_time = semester_data.beginn OR (seminare.start_time < semester_data.beginn AND (seminare.duration_time = -1 OR seminare.start_time + seminare.duration_time >= semester_data.beginn)))
                LEFT JOIN seminar_user ON (seminar_user.Seminar_id = seminare.Seminar_id AND seminar_user.status = 'dozent')
                LEFT JOIN auth_user_md5 ON (auth_user_md5.user_id = seminar_user.user_id) 
            WHERE (
                    CONCAT(seminare.VeranstaltungsNummer, ' ', seminare.Name) LIKE :input
                    OR CONCAT(auth_user_md5.Vorname, ' ', auth_user_md5.Nachname) LIKE :input
                    OR seminare.Untertitel LIKE :input
                    OR seminare.Beschreibung LIKE :input 
                    OR seminare.Ort LIKE :input 
                    OR seminare.Sonstiges LIKE :input
                )
                AND (semester_data.semester_id = :semester_id OR :semester_id = '')
                AND seminare.status NOT IN ('".implode("', '", studygroup_sem_types())."')
            GROUP BY seminare.Seminar_id
            ",
            _("Veranstaltung suchen"),
            "Seminar_id"
        );
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
