<?php

/*
 * Copyright (C) 2022 Manuel Faux <mfaux@conf.at>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
 * AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
 * OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace OPNsense\Diagnostics\Api;

use OPNsense\Base\ApiControllerBase;
use OPNsense\Core\Backend;
use OPNsense\Core\Config;
use Phalcon\Filter;

/**
 * Class IpsecController
 * @package OPNsense\Diagnostics\Api
 */
class IpsecController extends ApiControllerBase
{

    public function searchConnectionAction()
    {
        return $this->getStatusData('conn');
    }

    /**
     * retrieve security associations database content
     * @return mixed
     */
    public function searchSadAction()
    {
        $connection = $this->request->getPost('connection', 'string', "");
        return $this->getStatusData('sa', $connection);
    }

    /**
     * retrieve security policies database content
     * @return mixed
     */
    public function searchSpdAction()
    {
        return $this->getStatusData('sp');
    }

    private function getStatusData($db, $connection_filter = -1)
    {
        if ($this->request->isPost()) {
            $filter = new Filter([
                'query' => function ($value) {
                    return preg_replace("/[^0-9,a-z,A-Z, ,\/,*,\-,_,.,\#]/", "", $value);
                }
            ]);
            $searchPhrase = '';
            $sortBy = '';
            $itemsPerPage = $this->request->getPost('rowCount', 'int', 9999);
            $currentPage = $this->request->getPost('current', 'int', 1);

            if ($this->request->getPost('searchPhrase', 'string', '') != '') {
                $searchPhrase = $filter->sanitize($this->request->getPost('searchPhrase'), 'query');
            }
            
            if ($this->request->has('sort') && is_array($this->request->getPost("sort"))) {
                $tmp = array_keys($this->request->getPost("sort"));
                $sortBy = $tmp[0] . " " . $this->request->getPost("sort")[$tmp[0]];
            }

            $result = json_decode((new Backend())->configdpRun("ipsec list status $db",
                [$searchPhrase, $itemsPerPage, ($currentPage - 1) * $itemsPerPage, $sortBy, $connection_filter]), true);
            if ($result != null) {
                return [
                    'rows' => $result['rows'],
                    'rowCount' => $result['total'],
                    'total' => $result['total_entries'],
                    'current' => (int)$currentPage
                ];
            }
        }

        return [
            'rows' => [],
            'rowCount' => 0,
            'total' => 0,
            'current' => 0
        ];
    }
}
