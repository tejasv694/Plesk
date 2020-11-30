# Copyright 1999-2017. Plesk International GmbH. All rights reserved.
from plesk_config import get
import plesk_constants
import os

def deployer():
    return os.path.join(get("PRODUCT_ROOT_D"),"admin","bin","deployer")

def backup_sign():
    return os.path.join(get("PRODUCT_ROOT_D"),"admin","sbin","backup_sign")

def agents_dir():
    return os.path.join(get("PRODUCT_ROOT_D"),"PMM","agents")

def backup():
    return '/usr/bin/perl'

def plesk_backup_args():
    return [os.path.join(get("PRODUCT_ROOT_D"),"admin","bin","plesk_agent_manager")]

def pmmcli():
    return os.path.join(get("PRODUCT_ROOT_D"),"admin","bin","pmmcli")

def daemon():
    return os.path.join(get("PRODUCT_ROOT_D"),"admin","sbin","pmmcli_daemon")

def daemon_args():
    return []

def pmmcli_dir():
    return os.path.join(get("PRODUCT_ROOT_D"),"admin","share","pmmcli")

def pmm_ras():
    return os.path.join(get("PRODUCT_ROOT_D"),"admin","bin","pmm-ras")

def var_dir():
    return os.path.join(get("PRODUCT_ROOT_D"),"PMM","var")

def tasks_dir():
    return os.path.join(get("PRODUCT_ROOT_D"),"PMM","tasks")

def daemontasks_dir():
    return os.path.join(get("PRODUCT_ROOT_D"),"PMM","daemontasks")

def session_dir():
    return os.path.join(get("PRODUCT_ROOT_D"),"PMM","sessions")

def restore_session_dir():
    return os.path.join(get("PRODUCT_ROOT_D"),"PMM","rsessions")

def plesk_runner():
    return os.path.join(get("PRODUCT_ROOT_D"),"bin","sw-engine-pleskrun")

def plesk_runner_args():
    return ['-c',os.path.join(get("PRODUCT_ROOT_D"),"admin","conf","php.ini")]

def backup_encrypt():
    return os.path.join(get("PRODUCT_ROOT_D"),"admin","sbin","backup_encrypt")

def pmm_conflict_detector():
    return os.path.join(get("PRODUCT_ROOT_D"),"admin","plib","backup","Conflicts","Runner.php")

def pmm_conflict_resolver():
    return os.path.join(get("PRODUCT_ROOT_D"),"admin","plib","backup","Conflicts","Runner.php")

def pmm_dump_transformer():
    return os.path.join(get("PRODUCT_ROOT_D"),"admin","plib","backup","Transformations","TransformationRunner.php")

def tmp_directory():
    return os.path.join(get("PRODUCT_ROOT_D"),"tmp")


def plesk_logs_directory():
    return plesk_constants.PRODUCT_LOGS_D


def pmm_logs_directory():
    return os.path.join(plesk_constants.PRODUCT_LOGS_D,"PMM")


def pmm_logs_last_rotation_time_file():
    return os.path.join(pmm_logs_directory(), '.last-rotation-time')


def pmm_api_xml_protocols_schema():
    return os.path.join(get("PRODUCT_ROOT_D"),"PMM","schemas","pmm_api_xml_protocols.xsd")

def pmmcli_deployer_lock_file():
    return os.path.join(pmmcli_dir(), ".deployer_lock")

def pmmcli_daemon_lock_file():
    return os.path.join(pmmcli_dir(), ".daemon_lock")

def execution_result_schema():
    return os.path.join(get("PRODUCT_ROOT_D"),"PMM","schemas","execution_result.xsd")

def pmm_suspend_handler():
    return os.path.join(get("PRODUCT_ROOT_D"),"admin","plib","backup","suspend_handler","SuspendHandlerRunner.php")

def backup_restore_helper():
    return os.path.join(get("PRODUCT_ROOT_D"),"admin","bin","backup_restore_helper")

def plesk_migrator():
    return os.path.join(get("PRODUCT_ROOT_D"),"admin","sbin","modules","panel-migrator","plesk-migrator")
