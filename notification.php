<?
use Bitrix\Highloadblock\HighloadBlockTable as HLBT;

AddEventHandler("iblock", "OnBeforeIBlockElementUpdate", Array("NotificationHandler", "OnBeforeIBlockElementUpdateHandler"));
AddEventHandler("iblock", "OnAfterIBlockElementUpdate", Array("NotificationHandler", "OnAfterIBlockElementUpdateHandler"));

class NotificationHandler
{
    
    protected static $mwOldInfoBlockData;    

    /**
     * debugLog Дебаг
     */
    function debugLog($stat, $params, $isarray = false)
    {
        if($isarray){
            $log = date('d.m.Y H:i:s')."\r\n".$stat."\r\n";
            file_put_contents('debuglog.txt', $log . PHP_EOL, FILE_APPEND);
            file_put_contents('debuglog.txt', var_export($params, true) . PHP_EOL, FILE_APPEND);
        }else{
            $log = date('d.m.Y H:i:s')."\r\n".$stat.': '.$params;
            file_put_contents('debuglog.txt', $log . PHP_EOL, FILE_APPEND);
        }
    }

    function SendNotification($recipient, $message_id, $shedule = false)
    {
        /*mail(
            "netrebinsg@gmail.com", 
            "Тема сообщения", 
            "Сообщение"
        );*/

        //$recipient['EMAIL'], $recipient['ID']
        
        $eventName = "NOTIFY_CLIENT";
 
        $arFields = array(
          'FROM_EMAIL' => 'noreply@myweb24.ru',
          'RECIPIENT' => $recipient['EMAIL'],
          'TITLE' => '',
          'MESSAGE_TEXT' => '',
        );
         
        $arrSite = 's1';
         
        $event = new CEvent;
        $event->SendImmediate($eventName, $arrSite, $arFields, "N", $message_id);

        /**
         * Если событие требует повторного уведомления,
         * то запишем в highload блок данные для агента
         */
        if($shedule)
        {        
            // Добавляем день к текущей дате
            $date = new DateTime();        
            $stmp = MakeTimeStamp($date, "DD.MM.YYYY HH:MI:SS");
            $arrAdd = array("DD" => 1,);
            $stmp = AddToTimeStamp($arrAdd, $stmp);
            $date = date("d.m.Y H:i:s", $stmp);

            // Пишем в наш блок
            CModule::IncludeModule('highloadblock');

            $hlblock = HLBT::getById(1)->fetch();
            $entity = HLBT::compileEntity($hlblock);
            $entity_data_class = $entity->getDataClass();
            
            $result = $entity_data_class::add(
                array(
                    'UF_USER_ID' => $recipient['ID'],
                    'UF_TEMPLATE_ID' => $message_id,
                    'UF_DATE' => $date,                
                ));

            self::SendSheduledNotification();
        }        
    }

    /**
     * Функция для агента
     * отправляет уведомления 
     */
    function SendSheduledNotification()
    {
        $hlblock = HLBT::getById(1)->fetch();
        $entity = HLBT::compileEntity($hlblock);
        $entity_data_class = $entity->getDataClass();
        
        $arFilter = array(
            array(
                'LOGIC' => 'AND',
                array(
                    'UF_SEND' => '0',
                ),
                array(
                    '<=UF_DATE' => new \Bitrix\Main\Type\DateTime(),
                )
           )
        );

        $mwResult = $entity_data_class::getList(array(
           'select' => array('*'),
           'filter' => $arFilter
        ));

        while($mwElementFields = $mwResult->fetch())
        {
            $result = CUser::GetByID($mwElementFields['UF_USER_ID']);
            $mwUser = $result->Fetch();

            //self::SendNotification($mwUser, 'Уведомили через сутки', false);
            self::debugLog('Agent', $mwUser, true);

            $result = $entity_data_class::update($mwElementFields['ID'], array(
                'UF_SEND' => '1',              
            ));
            
        }

    }

    function GetNotificationFields($arFields)
    {

        //Получаем изменяемый элемент
        $arSort = array("ID" => "ASC");
        
        $arFilter = array(
            "IBLOCK_ID" => $arFields['IBLOCK_ID'], 
            "ID" => $arFields['ID'],
        );

        $arSelected = array(
            'ID',
            'NAME',
            'PROPERTY_BRIEF_STATUS',
            'PROPERTY_FILE_DESIGN',
            'PROPERTY_FILE_POSITIONING',
            'PROPERTY_CLIENT',
        );

        $mwElement = CIBlockElement::GetList(
            $arSort,
            $arFilter,
            false,
            false,
            $arSelected
        );  
            
        $mwResult = array();        
        while($mwElementFields = $mwElement->GetNext()){

            $mwResult['BRIEF_STATUS'] = $mwElementFields["PROPERTY_BRIEF_STATUS_VALUE"];
            $mwResult['FILE_DESIGN'] = $mwElementFields["PROPERTY_FILE_DESIGN_VALUE"];
            $mwResult['FILE_POSITIONING'] = $mwElementFields["PROPERTY_FILE_POSITIONING_VALUE"];
            $mwResult['CLIENT'] = $mwElementFields["PROPERTY_CLIENT_VALUE"];
            
        }

        return $mwResult;

    }

    /**
     * Событие до обновления инфоблока
     * @var array &$arFields - содержит уже измененнное состояние полей
     */
    function OnBeforeIBlockElementUpdateHandler(&$arFields)
    {
        /**
         * Проверка, что событие вызвано инфоблоком Бриф
         * и сохраняем свойства перед изменением в массив
         */
        if($arFields['IBLOCK_ID'] == 7)
        {            
            self::$mwOldInfoBlockData = self::GetNotificationFields($arFields);                        
        }      
    }


    /**
     * Событие после обновления инфоблока
     * @var array &$arFields - содержит уже измененнное состояние полей
     */
    function OnAfterIBlockElementUpdateHandler(&$arFields)
    {

        if($arFields['IBLOCK_ID'] == 7)
        {

            $mwNewInfoBlockData = self::GetNotificationFields($arFields);
            $mwOldInfoBlockData = self::$mwOldInfoBlockData;

            $shedule = false;

            /**
             * Обрабатываем события для клиента
             * Проверяем привязку к брифу и получаем доп информацию
             */
            if($mwNewInfoBlockData['CLIENT'])
            {
                $res = CUser::GetByID($mwNewInfoBlockData['CLIENT']);
                $mwUser = $res->Fetch();

                /**
                 * Проверка изменения свойства Статус инфоблока Бриф
                 */
                if($mwNewInfoBlockData['BRIEF_STATUS'] <> $mwOldInfoBlockData['BRIEF_STATUS'])
                {
                    
                    /**
                     * Событие: если Статус подписания договоров
                     */
                    if($mwNewInfoBlockData['BRIEF_STATUS'] == 'Подписание')
                    {                        
                        $mwMessageId = 30;                        
                    }

                    /**
                     * Событие: если Тайминг проекта в ЛК
                     */
                    if($mwNewInfoBlockData['BRIEF_STATUS'] == 'Тайминг проекта')
                    {                        
                        $mwMessageId = 31;                        
                    }
                }


                /**
                 * Проверка изменения свойства Файл дизайн инфоблока Бриф
                 */
                if($mwNewInfoBlockData['FILE_DESIGN'] <> $mwOldInfoBlockData['FILE_DESIGN'])
                {

                    /**
                     * Событие: Фирменный стиль/ дизайн упаковки готов. Выберите вариант
                     */                    
                    $mwMessageId = 32;
                    $shedule = true;
                }

                /**
                 * Проверка изменения свойства Файл позиционирования инфоблока Бриф
                 */
                if($mwNewInfoBlockData['FILE_POSITIONING'] <> $mwOldInfoBlockData['FILE_POSITIONING'])
                {

                    /**
                     * Событие: Бренд-позиционирование готово и выгружено в ЛК
                     */                    
                    $mwMessageId = 33; 
                    $shedule = true;
                }
                
                self::SendNotification($mwUser, $mwMessageId, $shedule);

            }
            // self::debugLog('before fields', self::$mwOldInfoBlockData, true);
            // self::debugLog('after fields', $mwNewInfoBlockData, true);
        }

    }

}

?>
