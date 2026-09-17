<?php

/*
|--------------------------------------------------------------------------
| FUELCORE Permissions Registry
|--------------------------------------------------------------------------
| Each permission slug maps to a Gate. Authorization is enforced server side
| through Gates + middleware, never by hiding menu items alone.
*/

return [

    'permissions' => [
        'user.manage', 'user.view',
        'station.manage', 'station.view', 'station.report',
        'pump.manage', 'pump.view',
        'nozzle.manage', 'nozzle.view',
        'product.manage', 'product.view',
        'tank.manage', 'tank.view',
        'sale.create', 'sale.view', 'sale.void',
        'payment.manage', 'payment.view',
        'customer.manage', 'customer.view',
        'fleet.manage', 'fleet.view',
        'delivery.manage', 'delivery.view',
        'inventory.manage', 'inventory.view',
        'reconciliation.manage', 'reconciliation.view',
        'shift.manage', 'shift.view',
        'expense.manage', 'expense.view', 'expense.approve',
        'supplier.manage', 'supplier.view',
        'report.view',
        'alert.manage', 'alert.view',
        'notification.view',
        'audit.view',
        'settings.manage',
        'integration.manage',
        'pos.use',
    ],

    'roles' => [
        'super_admin' => ['user.manage', 'user.view', 'station.manage', 'station.view', 'station.report', 'pump.manage', 'pump.view', 'nozzle.manage', 'nozzle.view', 'product.manage', 'product.view', 'tank.manage', 'tank.view', 'sale.create', 'sale.view', 'sale.void', 'payment.manage', 'payment.view', 'customer.manage', 'customer.view', 'fleet.manage', 'fleet.view', 'delivery.manage', 'delivery.view', 'inventory.manage', 'inventory.view', 'reconciliation.manage', 'reconciliation.view', 'shift.manage', 'shift.view', 'expense.manage', 'expense.view', 'expense.approve', 'supplier.manage', 'supplier.view', 'report.view', 'alert.manage', 'alert.view', 'notification.view', 'audit.view', 'settings.manage', 'integration.manage', 'pos.use'],
        'head_office_admin' => ['user.view', 'station.manage', 'station.view', 'station.report', 'pump.manage', 'pump.view', 'nozzle.manage', 'nozzle.view', 'product.manage', 'product.view', 'tank.manage', 'tank.view', 'sale.create', 'sale.view', 'sale.void', 'payment.manage', 'payment.view', 'customer.manage', 'customer.view', 'fleet.manage', 'fleet.view', 'delivery.manage', 'delivery.view', 'inventory.manage', 'inventory.view', 'reconciliation.manage', 'reconciliation.view', 'shift.manage', 'shift.view', 'expense.manage', 'expense.view', 'expense.approve', 'supplier.manage', 'supplier.view', 'report.view', 'alert.manage', 'alert.view', 'notification.view', 'audit.view', 'settings.manage', 'integration.manage', 'pos.use'],
        'station_manager' => ['station.view', 'station.report', 'pump.manage', 'pump.view', 'nozzle.manage', 'nozzle.view', 'tank.view', 'tank.manage', 'sale.create', 'sale.view', 'sale.void', 'payment.manage', 'payment.view', 'customer.manage', 'customer.view', 'fleet.view', 'delivery.view', 'inventory.view', 'reconciliation.view', 'shift.manage', 'shift.view', 'expense.manage', 'expense.view', 'supplier.view', 'report.view', 'alert.manage', 'alert.view', 'notification.view', 'pos.use'],
        'supervisor' => ['station.view', 'pump.view', 'nozzle.view', 'tank.view', 'sale.view', 'sale.create', 'payment.view', 'shift.view', 'report.view', 'alert.view', 'notification.view', 'pos.use'],
        'fuel_attendant' => ['station.view', 'pump.view', 'nozzle.view', 'tank.view', 'sale.create', 'sale.view', 'customer.view', 'shift.view', 'shift.manage', 'alert.view', 'notification.view', 'pos.use'],
        'cashier' => ['sale.create', 'sale.view', 'payment.manage', 'payment.view', 'reconciliation.manage', 'reconciliation.view', 'shift.view', 'station.view', 'report.view', 'alert.view', 'notification.view', 'pos.use'],
        'accountant' => ['report.view', 'payment.view', 'payment.manage', 'expense.view', 'expense.manage', 'expense.approve', 'reconciliation.manage', 'reconciliation.view', 'sale.view', 'audit.view', 'station.view', 'alert.view', 'notification.view'],
        'inventory_officer' => ['inventory.manage', 'inventory.view', 'delivery.manage', 'delivery.view', 'tank.manage', 'tank.view', 'supplier.manage', 'supplier.view', 'product.view', 'station.view', 'alert.view', 'notification.view'],
        'auditor' => ['audit.view', 'report.view', 'sale.view', 'payment.view', 'reconciliation.view', 'station.view', 'alert.view', 'notification.view'],
    ],
];