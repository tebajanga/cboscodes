<?php
/*************************************************************************************************
 * Copyright 2016 JPL TSolucio, S.L. -- This file is a part of TSOLUCIO coreBOS customizations.
 * You can copy, adapt and distribute the work under the "Attribution-NonCommercial-ShareAlike"
 * Vizsage Public License (the "License"). You may not use this file except in compliance with the
 * License. Roughly speaking, non-commercial users may share and modify this code, but must give credit
 * and share improvements. However, for proper details please read the full License, available at
 * http://vizsage.com/license/Vizsage-License-BY-NC-SA.html and the handy reference for understanding
 * the full license at http://vizsage.com/license/Vizsage-Deed-BY-NC-SA.html. Unless required by
 * applicable law or agreed to in writing, any software distributed under the License is distributed
 * on an  "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and limitations under the
 * License terms of Creative Commons Attribution-NonCommercial-ShareAlike 3.0 (the License).
 *************************************************************************************************/

class Valoracion2Mitigacion_FieldMapping extends cbupdaterWorker {

	public function applyChange() {
		if ($this->hasError()) {
			$this->sendError();
		}
		if ($this->isApplied()) {
			$this->sendMsg('Changeset '.get_class($this).' already applied!');
		} else {
			$this->sendMsg('This changeset create a FieldMapping Business Map that fills in catrsg when creating a MitigacionRiesgos record from ValoracionRiesgo');
			include_once 'include/Webservices/Create.php';
			include_once 'include/Webservices/Delete.php';
			global $current_user,$adb;
			$usrwsid = vtws_getEntityId('Users').'x'.$current_user->id;
			$res = $adb->pquery("SELECT cbmapid FROM vtiger_cbmap WHERE mapname=?", array('ValoracionRiesgo2MitigacionRiesgos'));
			$mapid = $adb->query_result($res, 0, 'cbmapid');
			$mapidws = vtws_getEntityId('cbMap').'x'.$mapid;
			vtws_delete($mapidws, $current_user);

			$default_values =  array(
				'mapname' => '',
				'maptype' => 'Mapping',
				'targetname' => '',
				'content' => '',
				'description' => '',
				'assigned_user_id' => $usrwsid,
			);
			$rec = $default_values;
			$rec['mapname'] = 'ValoracionRiesgo2MitigacionRiesgos';
			$rec['targetname'] = 'MitigacionRiesgos';
			$rec['content'] = '<map>
	<originmodule>
		<originname>ValoracionRiesgo</originname>
	</originmodule>
	<targetmodule>
		<targetname>MitigacionRiesgos</targetname>
	</targetmodule>
	<fields>
		<field>
			<fieldname>catrsg</fieldname>
			<Orgfields>
				<Orgfield>
					<OrgfieldName>catrsg</OrgfieldName>
				</Orgfield>
			</Orgfields>
		</field>
	</fields>
</map>';
			vtws_create('cbMap', $rec, $current_user);
			$this->sendMsg('Changeset '.get_class($this).' applied!');
			$this->markApplied(false);
		}
		$this->finishExecution();
	}
}
