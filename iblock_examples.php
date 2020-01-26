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
