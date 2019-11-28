<label class="blue bold">Регистрация / Авторизация через соц сети без попап окна</label>
<div class="soc-net-block">
    <!-- без формы авторизации соц сети не стартуют -->
    <div class="form" style="display: none;">
        <?$APPLICATION->IncludeComponent(
            "bitrix:system.auth.form",
            "",
            Array(
                "FORGOT_PASSWORD_URL" => "/login/password",
                "PROFILE_URL" => "/user",
                "REGISTER_URL" => "/this-page/",
                "SHOW_ERRORS" => "Y"
            )
        );?>    
    </div>

    <?
        $arResult["AUTH_SERVICES"] = false;
        if(CModule::IncludeModule("socialservices")) {
            $oAuthManager = new CSocServAuthManager();
            $arServices = $oAuthManager->GetActiveAuthServices($arResult);
            if(!empty($arServices)) $arResult["AUTH_SERVICES"] = $arServices;
        }

        if($arResult["AUTH_SERVICES"] && COption::GetOptionString("main", "allow_socserv_authorization", "Y") != "N") {           
            $APPLICATION->IncludeComponent("bitrix:socserv.auth.form", "your_template_auth", 
                array(
                  "AUTH_SERVICES"=>$arResult["AUTH_SERVICES"],
                  "SUFFIX"=>"form", 
                ), 
                $component, 
                array("HIDE_ICONS"=>"Y")
            );
        } 
     ?>
</div>
