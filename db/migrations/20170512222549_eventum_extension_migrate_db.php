<?php

/*
 * This file is part of the Eventum (Issue Tracking System) package.
 *
 * @copyright (c) Eventum Team
 * @license GNU General Public License, version 2 or later (GPL-2+)
 *
 * For the full copyright and license information,
 * please see the COPYING and AUTHORS files
 * that were distributed with this source code.
 */

use Eventum\Db\AbstractMigration;
use Eventum\Extension\BuiltinLegacyLoaderExtension;

class EventumExtensionMigrateDb extends AbstractMigration
{
    public function up()
    {
        $this->migratePartners();
        $this->migrateCustomFields();
        $this->migrateWorkflows();
        $this->migrateCustomers();
        $this->setupLegacyLoader();
    }

    /**
     * Setup BuiltinLegacyLoaderExtension being loaded by default
     */
    private function setupLegacyLoader()
    {
        $setup = Setup::get();
        $rf = new ReflectionClass(BuiltinLegacyLoaderExtension::class);
        $setup['extensions'][$rf->getName()] = $rf->getFileName();
        Setup::save();
    }

    private function migratePartners()
    {
        $callback = function ($code) {
            return Partner::getBackend($code);
        };
        $this->migrate('partner_project', 'pap_par_code', $callback);
    }

    private function migrateCustomFields()
    {
        $callback = function ($code) {
            return Custom_Field::_getBackend($code);
        };
        $this->migrate('custom_field', 'fld_backend', $callback);
    }

    private function migrateWorkflows()
    {
        $callback = function ($code) {
            return Workflow::getBackend($code);
        };
        $this->migrate('project', 'prj_workflow_backend', $callback);
    }

    private function migrateCustomers()
    {
        $callback = function ($code) {
            return CRM::_getBackend($code);
        };
        $this->migrate('project', 'prj_customer_backend', $callback);
    }

    private function migrate($table, $field, $callback)
    {
        $table = $this->getAdapter()->quoteColumnName($table);
        $column = $this->getAdapter()->quoteTableName($field);

        $st = $this->query("SELECT {$column} FROM {$table}");
        foreach ($st as $row) {
            $value = $row[$field];
            // nothing to convert for empty values
            if (!$value) {
                continue;
            }

            // lookup filename to class name
            $backend = $callback($value);
            $rc = new ReflectionClass($backend);
            $classname = $rc->getName();

            // use deterministic class name
            $classname = ucwords(str_replace('_', ' ', $classname));
            $classname = str_replace(' ', '_', $classname);

            // there's no placeholders support,
            // method to escape values not exported
            // but the class names should not need sql escaping
            // so just go with unescaped variant
            //
            // last known PR implementing params for execute:
            // https://github.com/robmorgan/phinx/pull/850
            $stmt = "UPDATE {$table} SET {$column} = '{$classname}' WHERE {$column} = '{$value}'";
            $this->execute($stmt);
        }
    }
}
