<?php

return [
    'fluid_precompile_module' => [
        'path' => '/fluid-precompile-module',
        'target' => \NamelessCoder\CmsFluidPrecompilerModule\Controller\FluidPrecompileController::class . '::moduleAction'
    ],
    'fluid_precompile' => [
        'path' => '/fluid-precompile',
        'target' => \NamelessCoder\CmsFluidPrecompilerModule\Controller\FluidPrecompileController::class . '::precompileAction'
    ]
];
