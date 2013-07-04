<?php
/**
 * flightzilla
 *
 * Copyright (c) 2012-2013, Hans-Peter Buniat <hpbuniat@googlemail.com>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 * * Redistributions of source code must retain the above copyright
 * notice, this list of conditions and the following disclaimer.
 *
 * * Redistributions in binary form must reproduce the above copyright
 * notice, this list of conditions and the following disclaimer in
 * the documentation and/or other materials provided with the
 * distribution.
 *
 * * Neither the name of Hans-Peter Buniat nor the names of his
 * contributors may be used to endorse or promote products derived
 * from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package flightzilla
 * @author Tibor Sari
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012-2013 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace Flightzilla\Model\Watchlist\Mapper;
use Flightzilla\Model\Watchlist;
use Zend\Db\Adapter\Driver\ResultInterface,
    Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Sql;

/**
 * Sqlite-Mapper for the Watchlist-Service
 *
 * @TODO: Still incomplete
 *
 * @author Tibor Sari
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012-2013 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class Sqlite implements MapperInterface {

    /**
     * The current user
     *
     * @var string
     */
    private $_sUser;

    /**
     * The watchlist-instance
     *
     * @param \Watchlist
     */
    private $_oWatchlist = null;

    /**
     * @var null|\Zend\Db\Adapter\Adapter
     */
    private $_oDb = null;

    /**
     * Create the sqlite-mapper
     *
     * @param \Zend\Db\Adapter\Adapter $oDb
     * @param $sUser
     */
    public function __construct(\Zend\Db\Adapter\Adapter $oDb, $sUser) {

        $this->_sUser = $sUser;
        $this->_oDb = $oDb;
        $this->_oWatchlist = new Watchlist($sUser);
    }

    /**
     * (non-PHPdoc)
     * @see MapperInterface::add()
     */
    public function add($iTicket) {

    }

    /**
     * (non-PHPdoc)
     * @see MapperInterface::remove()
     */
    public function remove($iTicket) {

    }

    /**
     * (non-PHPdoc)
     * @see MapperInterface::get()
     */
    public function get() {

        $sql = 'SELECT * from tickets t INNER JOIN  user u on t.userId = u.Id WHERE u.name = ?';

        $oSql = new Sql($this->_oDb);


        $result = $this->_oDb->query($sql, array($this->_sUser));

        if ($result instanceof ResultInterface && $result->isQueryResult()) {
            $resultSet = new ResultSet;
            $resultSet->initialize($result);

            foreach ($resultSet as $row) {
                echo $row->my_column . PHP_EOL;
            }
        }

        $this->_oWatchlist->add(169846);

        return $this->_oWatchlist;
    }


}
