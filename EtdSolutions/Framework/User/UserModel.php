<?php
/**
 * @package     EtdDirect
 *
 * @version     0.0.1
 * @copyright   Copyright (C) 2014 ETD Solutions, SARL Etudoo. Tous droits réservés.
 * @license     http://etdglobalone.com/LICENSE
 * @author      ETD Solutions http://etd-solutions.com
 */

namespace EtdSolutions\EtdDirect\Model;

use EtdSolutions\Framework\Model\ItemModel;
use Joomla\Database\DatabaseQuery;
use Joomla\Language\Text;

class UserModel extends ItemModel {

    /**
     * Méthode pour charger un utilisateur.
     *
     * @param int $id
     *
     * @return mixed
     */
    protected function loadItem($id) {

        $db = $this->getDb();

        $db->setQuery(
           $db->getQuery(true)
            ->select("*")
            ->from("#__users")
            ->where("id = " . (int) $id)
        );

        $item = $db->loadObject();

        return $item;

    }

}