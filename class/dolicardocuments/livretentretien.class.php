<?php
/* Copyright (C) 2022-2024 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    class/dolicardocuments/livretentretien.class.php
 * \ingroup dolicar
 * \brief   This file is a CRUD class file for LivretEntretien document
 */

// Load Saturne libraries
require_once __DIR__ . '/../../../saturne/class/saturnedocuments.class.php';

/**
 * Class for LivretEntretien document
 */
class LivretEntretien extends SaturneDocuments
{
    /**
     * @var string Module name
     */
    public $module = 'dolicar';

    /**
     * @var string Element type of object
     */
    public $element = 'livretentretien';

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        parent::__construct($db, $this->module, $this->element);
    }
}
