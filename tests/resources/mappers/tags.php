<?php

use Sirius\Orm\MapperConfig;

return [
    MapperConfig::TABLE   => 'tags',
    MapperConfig::COLUMNS => ['id', 'name'],
//    MapperConfig::RELATIONS => [
//        'products' => [
//            RelationConfig::FOREIGN_MAPPER  => 'products',
//            RelationConfig::TYPE            => RelationConfig::TYPE_MANY_TO_MANY,
//            RelationConfig::THROUGH_COLUMNS => ['position'],
//        ]
//    ]
];
