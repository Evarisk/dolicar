<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    core/tpl/registrationcertificatefr_file_tags.tpl.php
 * \ingroup dolicar
 * \brief   Template page for the tags of the files attached to a carte grise
 */

/**
 * The following vars must be defined :
 * Global   : $langs, $user
 * Objects  : $object (RegistrationCertificateFr)
 */

require_once __DIR__ . '/../../lib/dolicar_registrationcertificatefr.lib.php';

$fileTags = dolicar_get_registration_certificate_file_tags($object);
if (!empty($fileTags)) {
    // The empty choice first: its pill invites to add a tag, its line in the list removes the tag
    $tagChoices = ['' => ['label' => $langs->trans('FileTagAdd'), 'option' => $langs->trans('FileTagNone'), 'pictofa' => 'fa-tag', 'optionpictofa' => 'fa-times', 'css' => 'empty']];
    foreach (dolicar_get_file_tags() as $tagCode => $tagInfo) {
        $tagChoices[$tagCode] = ['label' => $langs->trans($tagInfo['label']), 'option' => $langs->trans($tagInfo['label']), 'pictofa' => $tagInfo['pictofa'], 'optionpictofa' => $tagInfo['pictofa'], 'css' => $tagInfo['css']];
    }
    $permissionToTag = $user->hasRight('dolicar', 'registrationcertificatefr', 'write');
    $tagSaveUrl      = $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&module_name=' . urlencode(GETPOST('module_name', 'alpha')) . '&object_type=' . urlencode($object->element) . '&action=set_file_tag';

    // The list of attached files has no hook per line: fileTag.js moves each tag into the line of its file
    print '<div id="dolicar-file-tags" class="hidden" data-title="' . dol_escape_htmltag($langs->trans('FileTag')) . '" data-url="' . dol_escape_htmltag($tagSaveUrl) . '" data-saved="' . dol_escape_htmltag($langs->trans('RecordSaved')) . '" data-error="' . dol_escape_htmltag($langs->trans('FileTagNotSaved')) . '">';
    foreach ($fileTags as $ecmFileId => $fileTag) {
        if ($permissionToTag) {
            // fileTag.js turns the list into a select2 whose selection and options are the pills of the tags
            print '<span class="dolicar-file-tag-picker" data-ecm-file-id="' . $ecmFileId . '">';
            print '<select class="dolicar-file-tag-select" name="file_tag_' . $ecmFileId . '" data-ecm-file-id="' . $ecmFileId . '" title="' . dol_escape_htmltag($langs->trans('FileTag')) . '">';
            foreach ($tagChoices as $tagCode => $choice) {
                print '<option value="' . $tagCode . '" data-label="' . dol_escape_htmltag($choice['label']) . '" data-picto="' . $choice['pictofa'] . '" data-option-picto="' . $choice['optionpictofa'] . '" data-css="' . $choice['css'] . '"' . ($fileTag == $tagCode ? ' selected' : '') . '>' . dol_escape_htmltag($choice['option']) . '</option>';
            }
            print '</select>';
            print '</span>';
        } elseif (isset($tagChoices[$fileTag]) && $fileTag != '') {
            // Read only: the pill of the tag, tinted like the document
            print '<span class="dolicar-file-tag dolicar-file-tag--' . $tagChoices[$fileTag]['css'] . '" data-ecm-file-id="' . $ecmFileId . '">';
            print '<i class="fas ' . $tagChoices[$fileTag]['pictofa'] . ' dolicar-file-tag-picto"></i>';
            print '<span class="dolicar-file-tag-label">' . dol_escape_htmltag($tagChoices[$fileTag]['label']) . '</span>';
            print '</span>';
        }
    }
    print '</div>';
}
