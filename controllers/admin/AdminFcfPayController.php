<?php
/**
 *  Copyright (C) FCF Inc. - All Rights Reserved
 *
 *
 *  @author    FCF Inc.
 *  @copyright 2020-2022 FCF Inc.
 *  @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

class AdminFcfPayController extends ModuleAdminController
{
    public function __construct()
    {
        $link = new Link();
        Tools::redirectAdmin($link->getAdminLink("AdminModules") . "&configure=fcfpay");
        parent::__construct();
    }
}
