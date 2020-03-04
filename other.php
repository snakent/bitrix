<?php
    /* Получить архивные заказы */
    $archive_order = \Bitrix\Sale\Archive\Manager::returnArchivedOrder($order["ORDER"]["ID"]);
