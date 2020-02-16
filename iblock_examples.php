CModule::IncludeModule("catalog");
CModule::IncludeModule('iblock');

/**
* Delete all elements from sections
*/
$result = CIBlockElement::GetList
(
    array("ID"=>"ASC"),
    array
    (
        'IBLOCK_ID' => 4,
        'SECTION_ID' => 0,
        'INCLUDE_SUBSECTIONS'=>'Y'
    )
);

while($element = $result->Fetch()) CIBlockElement::Delete($element['ID']);

/**
* Resave all elements by iblock ID
*/
set_time_limit(0);
CModule::IncludeModule('iblock');

$result = CIBlockElement::GetList
(
    array("ID"=>"ASC"),
    array
    (
        'IBLOCK_ID' => 4
    )
);

$el = new CIBlockElement;
$arFields = array(
    "MODIFIED_BY" => $USER->GetID(),
);

while($element = $result->Fetch()) $el->Update($element['ID'], $arFields);
