<?php
/* Copyright (C) 2022-2024 EVARISK <technique@evarisk.com>
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
 * \file    class/api_dolicar.class.php
 * \ingroup dolicar
 * \brief   REST API for DoliCar — RegistrationCertificateFr CRUD endpoints
 */

use Luracast\Restler\RestException;

require_once __DIR__ . '/registrationcertificatefr.class.php';

/**
 * API endpoints for DoliCar module — manages French vehicle registration certificates
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Dolicar extends DolibarrApi
{
    /**
     * @var string[] Mandatory fields checked on create
     */
    public static $FIELDS = [
        'a_registration_number',
    ];

    /**
     * @var RegistrationCertificateFr $registrationcertificatefr {@type RegistrationCertificateFr}
     */
    public $registrationcertificatefr;

    /**
     * Constructor
     */
    public function __construct()
    {
        global $db;

        $this->db                        = $db;
        $this->registrationcertificatefr = new RegistrationCertificateFr($this->db);
    }

    /**
     * Get a registration certificate by ID
     *
     * @param int $id ID of the registration certificate
     * @return array|mixed Object data
     *
     * @throws RestException 403 Forbidden
     * @throws RestException 404 Not found
     */
    public function get(int $id)
    {
        if (!DolibarrApiAccess::$user->hasRight('dolicar', 'registrationcertificatefr', 'read')) {
            throw new RestException(403);
        }

        $result = $this->registrationcertificatefr->fetch($id);

        if ($result <= 0) {
            throw new RestException(404, 'Registration certificate not found');
        }

        if (!DolibarrApi::_checkAccessToResource('registrationcertificatefr', $this->registrationcertificatefr->id, 'dolicar_registrationcertificatefr')) {
            throw new RestException(403, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        return $this->_cleanObjectDatas($this->registrationcertificatefr);
    }

    /**
     * Get a registration certificate by registration number (ref)
     *
     * @param string $ref Registration number (e.g. AB-123-CD)
     * @return array|mixed Object data
     *
     * @url GET ref/{ref}
     *
     * @throws RestException 403 Forbidden
     * @throws RestException 404 Not found
     */
    public function getByRef(string $ref)
    {
        if (!DolibarrApiAccess::$user->hasRight('dolicar', 'registrationcertificatefr', 'read')) {
            throw new RestException(403);
        }

        $result = $this->registrationcertificatefr->fetch(0, $ref);

        if ($result <= 0) {
            throw new RestException(404, 'Registration certificate not found');
        }

        if (!DolibarrApi::_checkAccessToResource('registrationcertificatefr', $this->registrationcertificatefr->id, 'dolicar_registrationcertificatefr')) {
            throw new RestException(403, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        return $this->_cleanObjectDatas($this->registrationcertificatefr);
    }

    /**
     * List registration certificates
     *
     * @param string $sortfield   Sort field (default: t.rowid)
     * @param string $sortorder   Sort order: ASC or DESC (default: ASC)
     * @param int    $limit       Maximum number of results (default: 100)
     * @param int    $page        Page number starting from 0 (default: 0)
     * @param string $sqlfilters  Additional SQL filter criteria. Example: "(t.a_registration_number:like:'AB-%')"
     * @param string $properties  Comma-separated list of properties to return. Empty = all
     * @return array List of registration certificate objects
     *
     * @throws RestException 403 Forbidden
     * @throws RestException 503 Query error
     */
    public function index(
        string $sortfield = 't.rowid',
        string $sortorder = 'ASC',
        int $limit = 100,
        int $page = 0,
        string $sqlfilters = '',
        string $properties = ''
    ): array {
        if (!DolibarrApiAccess::$user->hasRight('dolicar', 'registrationcertificatefr', 'read')) {
            throw new RestException(403);
        }

        $sql  = 'SELECT t.rowid';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'dolicar_registrationcertificatefr AS t';
        $sql .= ' WHERE t.entity IN (' . getEntity('registrationcertificatefr') . ')';
        $sql .= ' AND t.status >= 0';

        if ($sqlfilters) {
            $errormessage = '';
            $sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
            if ($errormessage) {
                throw new RestException(400, 'Error when validating parameter sqlfilters -> ' . $errormessage);
            }
        }

        $sql .= $this->db->order($sortfield, $sortorder);

        if ($limit > 0) {
            $offset = $limit * max(0, $page);
            $sql   .= $this->db->plimit($limit, $offset);
        }

        $result = $this->db->query($sql);

        if (!$result) {
            throw new RestException(503, 'Error when retrieving registration certificate list: ' . $this->db->lasterror());
        }

        $obj_ret = [];
        $num     = $this->db->num_rows($result);

        for ($i = 0; $i < $num; $i++) {
            $obj = $this->db->fetch_object($result);

            $certificate = new RegistrationCertificateFr($this->db);
            if ($certificate->fetch((int) $obj->rowid) > 0) {
                $obj_ret[] = $this->_filterObjectProperties($this->_cleanObjectDatas($certificate), $properties);
            }
        }

        return $obj_ret;
    }

    /**
     * Create a registration certificate
     *
     * Example: { "a_registration_number": "AB-123-CD", "fk_soc": 1 }
     *
     * @param array $request_data Request data
     * @phan-param ?array<string,mixed> $request_data
     * @phpstan-param ?array<string,mixed> $request_data
     * @return int ID of the created object
     *
     * @throws RestException 400 Bad request
     * @throws RestException 403 Forbidden
     * @throws RestException 500 Creation error
     */
    public function post($request_data = null): int
    {
        if (!DolibarrApiAccess::$user->hasRight('dolicar', 'registrationcertificatefr', 'write')) {
            throw new RestException(403);
        }

        $this->_validate($request_data);

        foreach ($request_data as $field => $value) {
            if ($field === 'caller') {
                $this->registrationcertificatefr->context['caller'] = sanitizeVal($value, 'aZ09');
                continue;
            }

            if ($field === 'id') {
                throw new RestException(400, 'Creating with id field is forbidden');
            }

            $this->registrationcertificatefr->$field = $this->_checkValForAPI($field, $value, $this->registrationcertificatefr);
        }

        $result = $this->registrationcertificatefr->create(DolibarrApiAccess::$user);

        if ($result < 0) {
            throw new RestException(500, 'Error creating registration certificate', array_merge([$this->registrationcertificatefr->error], $this->registrationcertificatefr->errors));
        }

        return (int) $this->registrationcertificatefr->id;
    }

    /**
     * Update a registration certificate
     *
     * @param int   $id           ID of the registration certificate
     * @param array $request_data Fields to update
     * @phan-param ?array<string,mixed> $request_data
     * @phpstan-param ?array<string,mixed> $request_data
     * @return array|mixed Updated object data
     *
     * @throws RestException 400 Bad request
     * @throws RestException 403 Forbidden
     * @throws RestException 404 Not found
     * @throws RestException 500 Update error
     */
    public function put(int $id, $request_data = null)
    {
        if (!DolibarrApiAccess::$user->hasRight('dolicar', 'registrationcertificatefr', 'write')) {
            throw new RestException(403);
        }

        if ($id <= 0) {
            throw new RestException(400, 'Invalid ID');
        }

        $result = $this->registrationcertificatefr->fetch($id);

        if ($result <= 0) {
            throw new RestException(404, 'Registration certificate not found');
        }

        if (!DolibarrApi::_checkAccessToResource('registrationcertificatefr', $this->registrationcertificatefr->id, 'dolicar_registrationcertificatefr')) {
            throw new RestException(403, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        foreach ($request_data as $field => $value) {
            if ($field === 'id') {
                continue;
            }

            if ($field === 'caller') {
                $this->registrationcertificatefr->context['caller'] = sanitizeVal($value, 'aZ09');
                continue;
            }

            if ($field === 'array_options' && is_array($value)) {
                foreach ($value as $index => $val) {
                    $this->registrationcertificatefr->array_options[$index] = $this->_checkValForAPI($field, $val, $this->registrationcertificatefr);
                }
                continue;
            }

            $this->registrationcertificatefr->$field = $this->_checkValForAPI($field, $value, $this->registrationcertificatefr);
        }

        if ($this->registrationcertificatefr->update(DolibarrApiAccess::$user) <= 0) {
            throw new RestException(500, 'Error updating registration certificate: ' . $this->registrationcertificatefr->error);
        }

        return $this->get($id);
    }

    /**
     * Delete a registration certificate
     *
     * @param int $id ID of the registration certificate
     * @return array
     * @phan-return array{success:array{code:int,message:string}}
     * @phpstan-return array{success:array{code:int,message:string}}
     *
     * @throws RestException 400 Bad request
     * @throws RestException 403 Forbidden
     * @throws RestException 404 Not found
     * @throws RestException 500 Deletion error
     */
    public function delete(int $id): array
    {
        if (!DolibarrApiAccess::$user->hasRight('dolicar', 'registrationcertificatefr', 'delete')) {
            throw new RestException(403);
        }

        if ($id <= 0) {
            throw new RestException(400, 'Invalid ID');
        }

        $result = $this->registrationcertificatefr->fetch($id);

        if ($result <= 0) {
            throw new RestException(404, 'Registration certificate not found');
        }

        if (!DolibarrApi::_checkAccessToResource('registrationcertificatefr', $this->registrationcertificatefr->id, 'dolicar_registrationcertificatefr')) {
            throw new RestException(403, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        if ($this->registrationcertificatefr->delete(DolibarrApiAccess::$user) <= 0) {
            throw new RestException(500, 'Error deleting registration certificate: ' . $this->registrationcertificatefr->error);
        }

        return [
            'success' => [
                'code'    => 200,
                'message' => 'Registration certificate deleted',
            ],
        ];
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
    /**
     * Clean sensitive object data before returning it via API
     *
     * @param  object $object Object to clean
     * @return object         Cleaned object
     */
    protected function _cleanObjectDatas($object)
    {
        // phpcs:enable
        $object = parent::_cleanObjectDatas($object);

        unset($object->lines);
        unset($object->note);
        unset($object->address);
        unset($object->barcode_type);
        unset($object->barcode_type_code);
        unset($object->barcode_type_label);
        unset($object->barcode_type_coder);

        return $object;
    }

    /**
     * Validate mandatory fields before create
     *
     * @param  ?array<string,mixed> $data Posted data
     * @return array<string,mixed>        Validated subset
     *
     * @throws RestException 400 Missing field
     */
    private function _validate($data): array
    {
        if ($data === null) {
            $data = [];
        }

        $certificate = [];

        foreach (self::$FIELDS as $field) {
            if (!isset($data[$field])) {
                throw new RestException(400, $field . ' field missing');
            }
            $certificate[$field] = $data[$field];
        }

        return $certificate;
    }
}
