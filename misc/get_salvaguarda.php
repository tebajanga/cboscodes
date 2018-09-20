/**
* Function to get FicherosFisicos related CatalogoSalvaguarda
* @param  integer   $id      - ficherosfisicosid
* returns related CatalogoSalvaguarda record in array format
*/
public function get_salvaguarda($id, $cur_tab_id, $rel_tab_id, $actions = false) {
    global $log, $singlepane_view,$currentModule;
    $log->debug("Entering get_salvaguarda(".$id.") method ...");
    $this_module = $currentModule;

    $related_module = vtlib_getModuleNameById($rel_tab_id);
    require_once "modules/$related_module/$related_module.php";
    $other = new $related_module();

    $parenttab = getParentTab();

    if ($singlepane_view == 'true') {
        $returnset = '&return_module='.$this_module.'&return_action=DetailView&return_id='.$id;
    } else {
        $returnset = '&return_module='.$this_module.'&return_action=CallRelatedList&return_id='.$id;
    }

    $query = "SELECT distinct vtiger_catalogosalvaguarda.*,vtiger_catalogosalvaguardacf.*,
        vtiger_crmentity.crmid, vtiger_crmentity.smownerid
        FROM vtiger_catalogosalvaguarda
        INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_catalogosalvaguarda.catalogosalvaguardaid
        INNER JOIN vtiger_catalogosalvaguardacf ON vtiger_catalogosalvaguardacf.catalogosalvaguardaid = vtiger_catalogosalvaguarda.catalogosalvaguardaid
        INNER JOIN vtiger_mitigacionriesgo ON vtiger_mitigacionriesgo.catsg = vtiger_catalogosalvaguarda.catalogosalvaguardaid
        INNER JOIN vtiger_valoracionriesgo ON vtiger_valoracionriesgo.valoracionriesgoid = vtiger_mitigacionriesgo.valrsg
        LEFT JOIN vtiger_users 
            ON vtiger_users.id=vtiger_crmentity.smownerid
        WHERE vtiger_crmentity.deleted = 0 AND vtiger_valoracionriesgo.acttto= $id";

    $return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

    if ($return_value == null) {
        $return_value = array();
    }

    $log->debug("Exiting get_salvaguarda method ...");
    return $return_value;
}