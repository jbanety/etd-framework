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
use Joomla\Registry\Registry;

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

        // On charge les droits.
        if (!empty($item->rights) && is_string($item->rights)) {
            $rights = new Registry($item->rights);
            $item->rights = $rights;
        }

        return $item;

    }

    protected function loadAdditionalItem($id, $idCompany)
    {
        // TODO: Implement loadAdditionalItem() method.
    }
}