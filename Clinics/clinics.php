<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Module\Clinics\Domain\ClinicsGateway;
use Gibbon\Module\Clinics\Domain\ClinicsStudentsGateway;

if (isModuleAccessible($guid, $connection2) == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $gibbonSchoolYearID = $gibbon->session->get('gibbonSchoolYearID');

    $page->breadcrumbs
        ->add(__m('View Clinics'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $clinicsGateway = $container->get(ClinicsGateway::class);

    $criteria = $clinicsGateway->newQueryCriteria()
        ->sortBy(['clinicsBlock.sequenceNumber','clinicsClinic.name'])
        ->fromPOST('clinics');

    $clinics = $clinicsGateway->queryClinicsBySchoolYear($criteria, $gibbonSchoolYearID);

    // DATA TABLE
    $table = DataTable::createPaginated('clinics', $criteria);

    $table->setTitle(__m('Clinics'));

    $table->modifyRows(function ($clinic, $row) {
        if ($clinic['active'] == 'N') $row->addClass('error');
        return $row;
    });

    $clinicsStudentsGateway = $container->get(ClinicsStudentsGateway::class);
    $criteria = $clinicsStudentsGateway->newQueryCriteria();

    $table->addExpandableColumn('moreDetails')
        ->format(function ($clinic) use ($clinicsStudentsGateway, $criteria) {
            $output = '';
            if ($clinic['description'] != '') {
                $output .= "<b>".__m("Description")."</b><br/>";
                $output .= $clinic['description']."<br/>";
            }
            $enrolment = $clinicsStudentsGateway->queryStudentEnrolmentByClinic($criteria, $clinic['clinicsClinicID']);
            if ($enrolment->getResultCount() > 0) {
                if ($clinic['description'] != '') {
                    $output .= "<br/>";
                }
                $output .= "<b>".__m("Enrolment")."</b><br/>";
                foreach ($enrolment AS $row) {
                    $output .= Format::name('', $row['preferredName'], $row['surname'], 'Student', true, true);
                    if ($row["status"] == "Assigned") {
                        $output .= "<i> (".__m("Assigned")."</i>)";
                    }
                    $output .= "<br/>";
                }
            }
            return $output;
        });

    $table->addColumn('blockName', __('Block'))
        ->sortable(['sequenceNumber', 'clinicsClinic.name']);

    $table->addColumn('name', __('Name'))
        ->sortable(['sequenceNumber','clinicsClinic.name']);

    $table->addColumn('department', __('Department'))
        ->sortable(['department']);

    $table->addColumn('enrolment', __('Enrolment'))
        ->notSortable()
        ->format(function ($clinic) use ($clinicsStudentsGateway, $criteria) {
            $enrolment = $clinicsStudentsGateway->queryStudentEnrolmentByClinic($criteria, $clinic['clinicsClinicID']);
            return $enrolment->getResultCount()." / ".$clinic['maxParticipants'];
        });

    $table->addColumn('space', __('Location'))
            ->sortable(['space']);

    echo $table->render($clinics);
}
