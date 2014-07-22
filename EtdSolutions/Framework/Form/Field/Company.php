<?php
/**
 * @package     etd-framework
 *
 * @version     0.0.1
 * @copyright   Copyright (C) 2014 ETD Solutions, SARL Etudoo. Tous droits réservés.
 * @license     http://etd-solutions.com/LICENSE
 * @author      ETD Solutions http://etd-solutions.com
 */

namespace EtdSolutions\Framework\Form;

use EtdSolutions\Framework\Application\Web;
use Joomla\Form\Field_List;
use Joomla\Form\Html\Select as HtmlSelect;

class Field_Company extends Field_List {

    /**
     * The form field type.
     *
     * @var    string
     */
    protected $type = 'Company';

    protected function getOptions() {

        $options = parent::getOptions();
        $db = Web::getInstance()->getDb();

        $db->setQuery(
          $db->getQuery(true)
             ->select('a.id AS value, a.name AS text')
             ->from('#__companies AS a')
             ->where('a.block = 0')
        );

        $companies = $db->loadObjectList();

        foreach ($companies as $company) {
            $options[] = HtmlSelect::option($company->value, $company->text);
        }

        return $options;

    }


}