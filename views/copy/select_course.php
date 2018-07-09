<form action="<?= PluginEngine::getLink($plugin, array(), "copy/to_course") ?>"
      method="post"
      class="default studip_form">

    <label>
        <?= _("Veranstaltung in Semester") ?>
        <select name="semester_id">
            <option value=""></option>
            <? foreach (array_reverse(Semester::getAll()) as $semester) : ?>
            <option value="<?= htmlReady($semester->getId()) ?>"<?= $semester->getId() == Semester::findCurrent()->getId() ? " selected" : "" ?>>
                <?= htmlReady($semester['name']) ?>
            </option>
            <? endforeach ?>
        </select>
    </label>

    <label>
        <?= _("Veranstaltung auswählen") ?>
        <?= QuickSearch::get("seminar_id", $search)->render() ?>
    </label>

    <h3><?= _("Gruppen") ?></h3>

    <ul class="clean">
        <? foreach (Statusgruppen::findByRange_id($_SESSION['SessionSeminar'] ?: Context::get()->id) as $statusgruppe) : ?>
        <li>
            <label>
                <input type="checkbox" name="gruppen[]" value="<?= htmlReady($statusgruppe->getId()) ?>" checked>
                <?= htmlReady($statusgruppe['name']) ?>
            </label>
        </li>
        <? endforeach ?>
    </ul>

    <div data-dialog-button>
        <?= \Studip\Button::create(_("Kopieren")) ?>
    </div>

</form>