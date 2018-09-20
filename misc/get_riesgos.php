/**
* Function to get FicherosFisicos related CatalogoRiesgos
* @param  integer   $id      - ficherosfisicosid
* returns related CatalogoRiesgos record in array format
*/
public function get_riesgos($id, $cur_tab_id, $rel_tab_id, $actions = false) {
    global $log, $singlepane_view,$currentModule;
    $log->debug("Entering get_riesgos(".$id.") method ...");
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

    $query = "SELECT distinct vtiger_catalogoriesgos.*,vtiger_catalogoriesgoscf.*,
        vtiger_crmentity.crmid, vtiger_crmentity.smownerid
        FROM vtiger_catalogoriesgos
        INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_catalogoriesgos.catalogoriesgosid
        INNER JOIN vtiger_catalogoriesgoscf ON vtiger_catalogoriesgoscf.catalogoriesgosid = vtiger_catalogoriesgos.catalogoriesgosid
        INNER JOIN vtiger_valoracionriesgo ON vtiger_valoracionriesgo.catrsg=vtiger_catalogoriesgos.catalogoriesgosid
        LEFT JOIN vtiger_users
            ON vtiger_users.id=vtiger_crmentity.smownerid
        WHERE vtiger_crmentity.deleted = 0 AND vtiger_valoracionriesgo.acttto= $id";

    $return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

    if ($return_value == null) {
        $return_value = array();
    }

    $log->debug("Exiting get_riesgos method ...");
    return $return_value;
}