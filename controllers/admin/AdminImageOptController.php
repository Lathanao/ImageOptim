<?php
/**
 * 2017 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 *  @author    thirty bees <modules@thirtybees.com>
 *  @copyright 2017 thirty bees
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

if (!defined('_TB_VERSION_')) {
   exit;
}
use ImageOpt\ImageOpt;
include_once(dirname(__FILE__).'/../../models/ImageOpt.php');

/**
 * Class AdminImageOptController
 */
class AdminImageOptController extends ModuleAdminController
{
    public $module;

    public function __construct()
    {
        $this->table = ImageOpt::TABLE;
        $this->list_id = ImageOpt::PRIMARY;
        $this->className = 'AdminImageOpt';
        $this->name = 'AdminImageOpt';
        $this->bootstrap = true;
        $this->_orderBy = ImageOpt::PRIMARY;
        $this->_orderWay = "asc";

        $this->addRowAction('view');
        $this->addRowAction('edit');
        $this->addRowAction('delete');


        $this->context = Context::getContext();
        $this->multishop_context = Shop::CONTEXT_SHOP;  // Only display this page in single store context
        $this->lang = false;

        // Allow bulk delete
        $this->bulk_actions = [
            'delete' => [
                'text'    => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon'    => 'icon-trash',
            ],
        ];

        // Set the fields_list to display in fields mode
        $this->fields_list = [
            ImageOpt::PRIMARY => [
                'title' => $this->l('ID'),
                'width' => 100,
                'type'  => 'text',
            ],
            'id_image'                   => [
                'title' => $this->l('Title'),
                'width' => 100,
                'type'  => 'text',
                'lang'  => true,
            ],
            'id_product'              => [
                'title'   => $this->l('Parent'),
                'width'   => 200,
                'type'    => 'text',
                'callback' => 'getParentTitleById',
            ],
            'active'                  => [
                'title'   => $this->l('Status'),
                'width'   => '70',
                'align'   => 'center',
                'active'  => 'status',
                'type'    => 'bool',
                'orderby' => false,
            ],
        ];

        // $tab = new Tab();
        // $tab->active = 1;
        // $tab->class_name = $this->className;
        // $tab->name = array();
        // foreach (Language::getLanguages(true) as $lang)
        //     $tab->name[$lang['id_lang']] = "ImageOptim";
        //
        // $tab->id_parent = -1;
        // $tab->module = $this->name;
        // return $tab->add();
        //
        // parent::__construct();
    }


    public function renderList()
    {
        return parent::renderList();
    }
}
