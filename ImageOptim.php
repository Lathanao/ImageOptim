<?php
/**
 * 2007-2016 PrestaShop
 *
 * Thirty Bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017 Thirty Bees
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
 * @author    Thirty Bees <modules@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017 Thirty Bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

use ImageOpt\ImageOpt;
use ImageOpt\ImageOrigin;
include_once(dirname(__FILE__).'/models/ImageOpt.php');
include_once(dirname(__FILE__).'/models/ImageOrigin.php');

if (!defined('_TB_VERSION_'))  exit;


class ImageOptim extends Module
{
    protected $_html = '';
    private $values = array(      "IMAGEOPTIM_ACTIVE"     => "1",
                                  "IMAGEOPTIM_URL"        => "http://api.resmush.it/ws.php?img=",
                                  "IMAGEOPTIM_QUALITY"    => "4500");

    public function __construct()
    {
        $this->name = 'ImageOptim';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Lathanao';
        $this->bootstrap = true;
        $this->secure_key = Tools::encrypt($this->name);

        parent::__construct();

        $this->displayName = $this->l('Optimize your images');
        $this->description = $this->l('Optimize add lighten the weight your shop\'s images.');
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => '1.6.99.99');
        if (!is_dir(_PS_PROD_IMG_DIR_)) {
            $this->warning = $this->l('Your images directory doesn\'t exist.');
        }
        if (!is_writable(_PS_PROD_IMG_DIR_)) {
            $this->warning = $this->l('Your images directory isn\'t writable.');
        }
    }

    public function install()
    {
        if (!ImageOpt::createDatabase() || !ImageOrigin::createDatabase())
            return false;

        return parent::install();
    }

    public function uninstall()
    {
        if (!ImageOpt::dropDatabase() || !ImageOrigin::dropDatabase())
            return false;

        return parent::uninstall();
    }

    public function getContent()
    {
        $this->context->controller->addCSS(_MODULE_DIR_.$this->name.'/css/admin_imageOptim.css');
        $this->context->controller->addJS(_MODULE_DIR_.$this->name.'/js/admin_imageOptim.js');

        $this->getSetup();
        $this->setSetup();
        // $this->updateOptimisationTable();

        ImageOrigin::dropDatabase();
        ImageOrigin::createDatabase();
        ImageOpt::dropDatabase();
        ImageOpt::createDatabase();
        $this->setImagesOriginInDb();
        $this->setImagesOptInDb();
        // $this->_html .= $this->admin_imageoptim->renderList();
        // $this->getOptimisedImage();
        $this->_html .= $this->renderList();
        $this->_html .= $this->renderForm();
        return $this->_html;
    }

    public function renderForm()
    {
        $formFields = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon'  => 'icon-cogs',
                ),
                'input'  => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Activate module'),
                        'name' => array_keys($this->values)[0],
                        'required' => false,
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'type'  => 'text',
                        'label' => $this->l('URL Optimiser'),
                        'name'  => 'IMAGEOPTIM_URL',
                    ),
                    array(
                        'type'  => 'text',
                        'label' => $this->l('Optimisation Quality'),
                        'name'  => 'IMAGEOPTIM_QUALITY',
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $this->fields_form = array();

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitForm';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getSetup(),
            'languages'    => $this->context->controller->getLanguages(),
            'id_language'  => $this->context->language->id,
        );

        return $helper->generateForm(array($formFields));
    }

    public function renderList()
    {
      $fields_list = array(
        'id' => array(
          'title' => $this->l('Id'),
          'search' => true,
          'width' => 30,
        ),
        'id_image' => array(
          'title' => $this->l('Id image'),
          'search' => true,
          'width' => 100,
        ),
        'id_type' => array(
          'title' => $this->l('type'),
          'search' => true,
          'width' => 100,
        ),
        'width' => array(
          'title' => $this->l('width'),
          'search' => true,
          'width' => 40,
        ),
        'height' => array(
          'title' => $this->l('height'),
          'search' => true,
          'width' => 40,
        ),
        'md5_origin' => array(
          'title' => $this->l('Md5'),
          'search' => true,
        ),
        'weight_origin' => array(
          'title' => $this->l('Weight before'),
        ),
        'weight_opt' => array(
          'title' => $this->l('Weight after'),
        ),
        'rate' => array(
          'title' => $this->l('Rate'),
          'search' => true,
        ),
        'Quality' => array(
          'title' => $this->l('Quality'),
          'search' => true,
        ),
      );

      $helper_list = New HelperList();
      $helper_list->module = $this;
      $helper_list->title = $this->l('Original Images list');
      $helper_list->shopLinkType = '';
      $helper_list->no_link = true;
      $helper_list->show_toolbar = true;
      $helper_list->simple_header = false;
      $helper_list->identifier = 'id';
      $helper_list->table = 'merged';
      $helper_list->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name;
      $helper_list->token = Tools::getAdminTokenLite('AdminModules');

      $this->_helperlist = $helper_list;

      $listImages = $this->getListImageOpt();
      $helper_list->listTotal = count($listImages);

      /* Paginate the result */
      $page = ($page = Tools::getValue('submitFilter'.$helper_list->table)) ? $page : 1;
      $pagination = ($pagination = Tools::getValue($helper_list->table.'_pagination')) ? $pagination : 50;
      $listImages = $this->paginateSubscribers($listImages, $page, $pagination);

      $helper_list->actions = array('Generate');
      $helper_list->bulk_actions = [
          'generateBulkImages' => ['text' => $this->l('Generate Bulk Images'), 'icon' => 'icon-refresh'],
      ];

      return $helper_list->generateList($listImages, $fields_list);
    }



    public function getListImageOpt()
    {
      $dbquery = new DbQuery();
      $dbquery->select('iop.id, ior.id_image, iop.id_type, iop.width, iop.height, iop.weight_origin, iop.weight_opt,  (iop.weight_opt / iop.weight_origin * 100) as rate, iop.quality, iop.md5_origin, iop.date_upd');
      $dbquery->from('image_origin', 'ior');
      $dbquery->leftJoin('image_opt', 'iop', 'ior.`id_image` = iop.`id_image`');

      return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($dbquery->build());
    }

    public function getListImageOrigin()
    {
      $dbquery = new DbQuery();
      $dbquery->select('*, img.`id_image` AS `id`');
      $dbquery->from('image_origin', 'img');
      return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($dbquery->build());
    }

    public function paginateSubscribers($subscribers, $page = 1, $pagination = 50)
    {
        if(count($subscribers) > $pagination)
          $subscribers = array_slice($subscribers, $pagination * ($page - 1), $pagination);

        return $subscribers;
    }

    public function displayGenerateLink($token = null, $id, $name = null)
    {
        $imageOpt = new ImageOpt($id);

        $this->smarty->assign(array(
          'href' => $this->context->link->getAdminLink('AdminModules', true/*token*/, null/*sfrouteparam*/, array(  "ajax"=> true,
                                                                                                                    "configure"=> $this->name,
                                                                                                                    "action"=> "GenerateOptimImage",
                                                                                                                    "configure"=> $this->name,
                                                                                                                    "key"=> $this->secure_key,
                                                                                                                    "GenerateOptimImage"=> $id)),
          'link' => $this->context->link->getImageLink("optim", $imageOpt->id_image, $imageOpt->id_type),
          'action' => $this->l('Generate'),
          'disable' => !((int)$id > 0),
        ));

        return $this->display(__FILE__, 'views/admin/list_action_generate.tpl');
    }

    public function getOptimisedImage()
    {
        $imageTypes = ImageType::getImagesTypes();
          // Tools::d($this->context->link->getImageLink('taggle'/*name*/, '20'/*ids*/, 'cart_default'/*type*/));
        define('WEBSERVICE', 'http://api.resmush.it/ws.php?img=');

        $demo = "https://front.thirtybees.com";


        if (Tools::isSubmit("id_image"))
            die ('error');
        $image = new image(Tools::getValue("id_image"));

        foreach ($imageTypes as $key => $type) {
            $path = _PS_PROD_IMG_DIR_.$image->getExistingImgPath().'-'.$type["name"].'.'.$image->image_format;
            $url = $this->context->link->getImageLink("optim", $image->id_image, $type["name"]);
            $result = json_decode(file_get_contents(WEBSERVICE . $url));
            if(isset($result->error)){
                die($result->error);
            } else {
                move_uploaded_file($result->dest, $path);
            }
        }

        $image = new image($valueimage["id_image"]);
        $path = _PS_PROD_IMG_DIR_.$image->getExistingImgPath().'.'.$image->image_format;

        define('WEBSERVICE', 'http://api.resmush.it/ws.php?img=');
        $s = 'https://store.thirtybees.com/303-thickbox_default/sucuri-module.jpg';
        $s = 'https://resmush.it/assets/images/jpg_example_original.jpg';
        $o = json_decode(file_get_contents(WEBSERVICE . $s));

        if(isset($o->error)){
          die('Error');
        }
        return $o->dest;
    }

    public function getSetup($Multilang = null /*Need multilang for admin, no need for front */)
    {
        $idLang = $this->context->language->id;
        $languages = Language::getLanguages(false);

        foreach ($this->values as $key => $value)
            if ($Multilang && is_array($value))
                foreach ($languages as $lang)
                    $this->values[$key][$lang['id_lang']] = Configuration::get($key, $lang['id_lang']);
            else
                $this->values[$key] = Configuration::get($key, $idLang);

        return $this->values;
    }

    public function setImagesOriginInDb()
    {
        $idLang = $this->context->language->id;
        $products = Product::getProducts(   $idLang,
                                            0 /*$start*/,
                                            0 /*$limit*/,
                                            "date_upd" /*$orderBy*/,
                                            "asc" /*$orderWay*/,
                                            false /*$idCategory*/,
                                            false /*$onlyActive*/,
                                            null /*$context*/  );

        foreach ($products as $key => $value) {

            $images = Image::getImages($idLang, $value["id_product"], null);

            foreach ($images as $keyimage => $valueimage) {

                // if (Validate::isLoadedObject(new ImageOpt($valueimage["id_image"])));
                //     continue;

                $image = new image($valueimage["id_image"]);
                $path = _PS_PROD_IMG_DIR_.$image->getExistingImgPath().'.'.$image->image_format;
                $info = getimagesize($path);

                $imageOpt = new ImageOrigin();
                $imageOpt->id_image = $image->id_image;
                $imageOpt->id_product = $image->id_product;
                $imageOpt->width = $info[0];
                $imageOpt->height = $info[1];
                $imageOpt->weight = filesize ( $path );
                $imageOpt->md5 = (string)md5($path);
                $imageOpt->path = $path;

                $imageOpt->save();
            }
        }
    }

    public function setImagesOptInDb()
    {
        $idLang = $this->context->language->id;

        $imageTypes = array_filter(ImageTypeCore::getImagesTypes(), function($val, $key) {
            return $val["products"] === "1";
        }, ARRAY_FILTER_USE_BOTH);

        $imageList = $this->getListImageOrigin();

        foreach ($imageList as $keyImage => $valueImage) {

            foreach ($imageTypes as $keyType => $valueTypes) {

                $image = new image($valueImage["id_image"]);
                $path = _PS_PROD_IMG_DIR_.$image->getExistingImgPath().'-'.$valueTypes['name'].'.'.$image->image_format;
                $info = getimagesize($path);

                $imageOpt = new ImageOpt();

                $imageOpt->id_image = $valueImage["id_image"];
                $imageOpt->id_type = $valueTypes['name'];
                $imageOpt->width = $info[0];
                $imageOpt->height = $info[1];
                $imageOpt->weight_origin = filesize ( $path );
                $imageOpt->md5_origin = (string)md5($path);
                $imageOpt->path = $path;

                $imageOpt->save();
            }
        }

    }

    public function setSetup()
    {
        $languages = Language::getLanguages(false);

        foreach ($_POST as $key => $value)
            if (array_key_exists($key, $this->values))
                Configuration::updateValue($key, $value, true);
            elseif (array_key_exists(substr($key, 0, -2), $this->values))
                Configuration::updateValue(substr($key, 0, -2), array( substr($key,-1) => $value), true);

        return true;
    }

    public function ajaxProcessGenerateOptimImage()
    {
        echo 'coucou';
    }
}
