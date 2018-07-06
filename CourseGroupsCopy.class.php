<?php

class CourseGroupsCopy extends StudIPPlugin implements SystemPlugin
{

    public function __construct()
    {
        parent::__construct();
        $course_id = $_SESSION['SessionSeminar'] ?: Context::get()->id;
        if (strpos($_SERVER['REQUEST_URI'], 'admin_statusgruppe.php') && $GLOBALS['perm']->have_studip_perm("tutor", $course_id)) {
            NotificationCenter::addObserver(
                $this,
                'addSidebarAction',
                'SidebarWillRender'
            );
        }
    }

    public function addSidebarAction($event, Sidebar $sidebar)
    {
        $actions = new ActionsWidget();
        $actions->addLink(
            _("Gruppen kopieren"),
            PluginEngine::getURL($this, array(), "copy/select_course"),
            version_compare($GLOBALS['SOFTWARE_VERSION'], "3.4", "<")
                ? Assets::image_path("icons/16/blue/group2")
                : Icon::create("group2", "clickable"),
            array('data-dialog' => 1)
        );
        $sidebar->addWidget($actions);

    }
}