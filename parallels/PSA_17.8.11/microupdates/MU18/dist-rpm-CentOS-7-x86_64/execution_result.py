#!/usr/bin/env python

#
# Generated Tue Aug 21 17:04:23 2018 by generateDS.py.
#

import sys
import getopt
from string import lower as str_lower
from xml.dom import minidom
from xml.dom import Node

#
# If you have installed IPython you can uncomment and use the following.
# IPython is available from http://ipython.scipy.org/.
#

## from IPython.Shell import IPShellEmbed
## args = ''
## ipshell = IPShellEmbed(args,
##     banner = 'Dropping into IPython',
##     exit_msg = 'Leaving Interpreter, back to program.')

# Then use the following line where and when you want to drop into the
# IPython shell:
#    ipshell('<some message> -- Entering ipshell.\nHit Ctrl-D to exit')

#
# Support/utility functions.
#

def showIndent(outfile, level):
    for idx in range(level):
        outfile.write('    ')

def quote_xml(inStr):
    s1 = (isinstance(inStr, basestring) and inStr or
          '%s' % inStr)
    s1 = s1.replace('&', '&amp;')
    s1 = s1.replace('<', '&lt;')
    s1 = s1.replace('>', '&gt;')
    return s1

def quote_attrib(inStr):
    s1 = (isinstance(inStr, basestring) and inStr or
          '%s' % inStr)
    s1 = s1.replace('&', '&amp;')
    s1 = s1.replace('"', '&quot;')
    s1 = s1.replace('<', '&lt;')
    s1 = s1.replace('>', '&gt;')
    return s1

def quote_python(inStr):
    s1 = inStr
    if s1.find("'") == -1:
        if s1.find('\n') == -1:
            return "'%s'" % s1
        else:
            return "'''%s'''" % s1
    else:
        if s1.find('"') != -1:
            s1 = s1.replace('"', '\\"')
        if s1.find('\n') == -1:
            return '"%s"' % s1
        else:
            return '"""%s"""' % s1


class MixedContainer:
    # Constants for category:
    CategoryNone = 0
    CategoryText = 1
    CategorySimple = 2
    CategoryComplex = 3
    # Constants for content_type:
    TypeNone = 0
    TypeText = 1
    TypeString = 2
    TypeInteger = 3
    TypeFloat = 4
    TypeDecimal = 5
    TypeDouble = 6
    TypeBoolean = 7
    def __init__(self, category, content_type, name, value):
        self.category = category
        self.content_type = content_type
        self.name = name
        self.value = value
    def getCategory(self):
        return self.category
    def getContenttype(self, content_type):
        return self.content_type
    def getValue(self):
        return self.value
    def getName(self):
        return self.name
    def export(self, outfile, level, name):
        if self.category == MixedContainer.CategoryText:
            outfile.write(self.value)
        elif self.category == MixedContainer.CategorySimple:
            self.exportSimple(outfile, level, name)
        else:    # category == MixedContainer.CategoryComplex
            self.value.export(outfile, level, name)
    def exportSimple(self, outfile, level, name):
        if self.content_type == MixedContainer.TypeString:
            outfile.write('<%s>%s</%s>' % (self.name, self.value, self.name))
        elif self.content_type == MixedContainer.TypeInteger or \
                self.content_type == MixedContainer.TypeBoolean:
            outfile.write('<%s>%d</%s>' % (self.name, self.value, self.name))
        elif self.content_type == MixedContainer.TypeFloat or \
                self.content_type == MixedContainer.TypeDecimal:
            outfile.write('<%s>%f</%s>' % (self.name, self.value, self.name))
        elif self.content_type == MixedContainer.TypeDouble:
            outfile.write('<%s>%g</%s>' % (self.name, self.value, self.name))
    def exportLiteral(self, outfile, level, name):
        if self.category == MixedContainer.CategoryText:
            showIndent(outfile, level)
            outfile.write('MixedContainer(%d, %d, "%s", "%s"),\n' % \
                (self.category, self.content_type, self.name, self.value))
        elif self.category == MixedContainer.CategorySimple:
            showIndent(outfile, level)
            outfile.write('MixedContainer(%d, %d, "%s", "%s"),\n' % \
                (self.category, self.content_type, self.name, self.value))
        else:    # category == MixedContainer.CategoryComplex
            showIndent(outfile, level)
            outfile.write('MixedContainer(%d, %d, "%s",\n' % \
                (self.category, self.content_type, self.name,))
            self.value.exportLiteral(outfile, level + 1)
            showIndent(outfile, level)
            outfile.write(')\n')


class _MemberSpec(object):
    def __init__(self, name='', data_type='', container=0):
        self.name = name
        self.data_type = data_type
        self.container = container
    def set_name(self, name): self.name = name
    def get_name(self): return self.name
    def set_data_type(self, data_type): self.data_type = data_type
    def get_data_type(self): return self.data_type
    def set_container(self, container): self.container = container
    def get_container(self): return self.container


#
# Data representation classes.
#

class ExecutionResultMixed:
    subclass = None
    superclass = None
    def __init__(self, status=None, log_location='', backup=None, transfer=None, restore=None):
        self.status = status
        self.log_location = log_location
        self.backup = backup
        self.transfer = transfer
        self.restore = restore
    def factory(*args_, **kwargs_):
        if ExecutionResultMixed.subclass:
            return ExecutionResultMixed.subclass(*args_, **kwargs_)
        else:
            return ExecutionResultMixed(*args_, **kwargs_)
    factory = staticmethod(factory)
    def get_backup(self): return self.backup
    def set_backup(self, backup): self.backup = backup
    def get_transfer(self): return self.transfer
    def set_transfer(self, transfer): self.transfer = transfer
    def get_restore(self): return self.restore
    def set_restore(self, restore): self.restore = restore
    def get_status(self): return self.status
    def set_status(self, status): self.status = status
    def get_log_location(self): return self.log_location
    def set_log_location(self, log_location): self.log_location = log_location
    def export(self, outfile, level, namespace_='', name_='ExecutionResultMixed'):
        showIndent(outfile, level)
        outfile.write('<%s%s' % (namespace_, name_))
        self.exportAttributes(outfile, level, namespace_, name_='ExecutionResultMixed')
        outfile.write('>\n')
        self.exportChildren(outfile, level + 1, namespace_, name_)
        showIndent(outfile, level)
        outfile.write('</%s%s>\n' % (namespace_, name_))
    def exportAttributes(self, outfile, level, namespace_='', name_='ExecutionResultMixed'):
        outfile.write(' status="%s"' % str(self.get_status()))
        if self.get_log_location() is not None:
            outfile.write(' log-location="%s"' % (quote_attrib(self.get_log_location()), ))
    def exportChildren(self, outfile, level, namespace_='', name_='ExecutionResultMixed'):
        if self.get_backup() != None :
            if self.backup:
                self.backup.export(outfile, level, namespace_, name_='backup')
        if self.get_transfer() != None :
            if self.transfer:
                self.transfer.export(outfile, level, namespace_, name_='transfer')
        if self.get_restore() != None :
            if self.restore:
                self.restore.export(outfile, level, namespace_, name_='restore')
    def exportLiteral(self, outfile, level, name_='ExecutionResultMixed'):
        level += 1
        self.exportLiteralAttributes(outfile, level, name_)
        self.exportLiteralChildren(outfile, level, name_)
    def exportLiteralAttributes(self, outfile, level, name_):
        showIndent(outfile, level)
        outfile.write('status = "%s",\n' % (self.get_status(),))
        showIndent(outfile, level)
        outfile.write('log_location = "%s",\n' % (self.get_log_location(),))
    def exportLiteralChildren(self, outfile, level, name_):
        if self.backup:
            showIndent(outfile, level)
            outfile.write('backup=ExecutionResult(\n')
            self.backup.exportLiteral(outfile, level, name_='backup')
            showIndent(outfile, level)
            outfile.write('),\n')
        if self.transfer:
            showIndent(outfile, level)
            outfile.write('transfer=ExecutionResult(\n')
            self.transfer.exportLiteral(outfile, level, name_='transfer')
            showIndent(outfile, level)
            outfile.write('),\n')
        if self.restore:
            showIndent(outfile, level)
            outfile.write('restore=ExecutionResultRestore(\n')
            self.restore.exportLiteral(outfile, level, name_='restore')
            showIndent(outfile, level)
            outfile.write('),\n')
    def build(self, node_):
        attrs = node_.attributes
        self.buildAttributes(attrs)
        for child_ in node_.childNodes:
            nodeName_ = child_.nodeName.split(':')[-1]
            self.buildChildren(child_, nodeName_)
    def buildAttributes(self, attrs):
        if attrs.get('status'):
            self.status = attrs.get('status').value
        if attrs.get('log-location'):
            self.log_location = attrs.get('log-location').value
    def buildChildren(self, child_, nodeName_):
        if child_.nodeType == Node.ELEMENT_NODE and \
            nodeName_ == 'backup':
            obj_ = ExecutionResult.factory()
            obj_.build(child_)
            self.set_backup(obj_)
        elif child_.nodeType == Node.ELEMENT_NODE and \
            nodeName_ == 'transfer':
            obj_ = ExecutionResult.factory()
            obj_.build(child_)
            self.set_transfer(obj_)
        elif child_.nodeType == Node.ELEMENT_NODE and \
            nodeName_ == 'restore':
            obj_ = ExecutionResultRestore.factory()
            obj_.build(child_)
            self.set_restore(obj_)
# end class ExecutionResultMixed


class ExecutionResultRestore:
    subclass = None
    superclass = None
    def __init__(self, status=None, log_location='', conflict_resolve=None, importxx=None, deploy=None):
        self.status = status
        self.log_location = log_location
        self.conflict_resolve = conflict_resolve
        self.importxx = importxx
        self.deploy = deploy
    def factory(*args_, **kwargs_):
        if ExecutionResultRestore.subclass:
            return ExecutionResultRestore.subclass(*args_, **kwargs_)
        else:
            return ExecutionResultRestore(*args_, **kwargs_)
    factory = staticmethod(factory)
    def get_conflict_resolve(self): return self.conflict_resolve
    def set_conflict_resolve(self, conflict_resolve): self.conflict_resolve = conflict_resolve
    def get_import(self): return self.importxx
    def set_import(self, importxx): self.importxx = importxx
    def get_deploy(self): return self.deploy
    def set_deploy(self, deploy): self.deploy = deploy
    def get_status(self): return self.status
    def set_status(self, status): self.status = status
    def get_log_location(self): return self.log_location
    def set_log_location(self, log_location): self.log_location = log_location
    def export(self, outfile, level, namespace_='', name_='ExecutionResultRestore'):
        showIndent(outfile, level)
        outfile.write('<%s%s' % (namespace_, name_))
        self.exportAttributes(outfile, level, namespace_, name_='ExecutionResultRestore')
        outfile.write('>\n')
        self.exportChildren(outfile, level + 1, namespace_, name_)
        showIndent(outfile, level)
        outfile.write('</%s%s>\n' % (namespace_, name_))
    def exportAttributes(self, outfile, level, namespace_='', name_='ExecutionResultRestore'):
        outfile.write(' status="%s"' % str(self.get_status()))
        if self.get_log_location() is not None:
            outfile.write(' log-location="%s"' % (quote_attrib(self.get_log_location()), ))
    def exportChildren(self, outfile, level, namespace_='', name_='ExecutionResultRestore'):
        if self.get_conflict_resolve() != None :
            if self.conflict_resolve:
                self.conflict_resolve.export(outfile, level, namespace_, name_='conflict-resolve')
        if self.get_import() != None :
            if self.importxx:
                self.importxx.export(outfile, level, namespace_, name_='import')
        if self.get_deploy() != None :
            if self.deploy:
                self.deploy.export(outfile, level, namespace_, name_='deploy')
    def exportLiteral(self, outfile, level, name_='ExecutionResultRestore'):
        level += 1
        self.exportLiteralAttributes(outfile, level, name_)
        self.exportLiteralChildren(outfile, level, name_)
    def exportLiteralAttributes(self, outfile, level, name_):
        showIndent(outfile, level)
        outfile.write('status = "%s",\n' % (self.get_status(),))
        showIndent(outfile, level)
        outfile.write('log_location = "%s",\n' % (self.get_log_location(),))
    def exportLiteralChildren(self, outfile, level, name_):
        if self.conflict_resolve:
            showIndent(outfile, level)
            outfile.write('conflict_resolve=ExecutionResult(\n')
            self.conflict_resolve.exportLiteral(outfile, level, name_='conflict_resolve')
            showIndent(outfile, level)
            outfile.write('),\n')
        if self.importxx:
            showIndent(outfile, level)
            outfile.write('importxx=ExecutionResult(\n')
            self.importxx.exportLiteral(outfile, level, name_='import')
            showIndent(outfile, level)
            outfile.write('),\n')
        if self.deploy:
            showIndent(outfile, level)
            outfile.write('deploy=ExecutionResult(\n')
            self.deploy.exportLiteral(outfile, level, name_='deploy')
            showIndent(outfile, level)
            outfile.write('),\n')
    def build(self, node_):
        attrs = node_.attributes
        self.buildAttributes(attrs)
        for child_ in node_.childNodes:
            nodeName_ = child_.nodeName.split(':')[-1]
            self.buildChildren(child_, nodeName_)
    def buildAttributes(self, attrs):
        if attrs.get('status'):
            self.status = attrs.get('status').value
        if attrs.get('log-location'):
            self.log_location = attrs.get('log-location').value
    def buildChildren(self, child_, nodeName_):
        if child_.nodeType == Node.ELEMENT_NODE and \
            nodeName_ == 'conflict-resolve':
            obj_ = ExecutionResult.factory()
            obj_.build(child_)
            self.set_conflict_resolve(obj_)
        elif child_.nodeType == Node.ELEMENT_NODE and \
            nodeName_ == 'import':
            obj_ = ExecutionResult.factory()
            obj_.build(child_)
            self.set_import(obj_)
        elif child_.nodeType == Node.ELEMENT_NODE and \
            nodeName_ == 'deploy':
            obj_ = ExecutionResult.factory()
            obj_.build(child_)
            self.set_deploy(obj_)
# end class ExecutionResultRestore


class ExecutionResult:
    subclass = None
    superclass = None
    def __init__(self, status=None, local_dump_created=False, log_location='', export_dump_created=False, message=None, object=None):
        self.status = status
        self.local_dump_created = local_dump_created
        self.log_location = log_location
        self.export_dump_created = export_dump_created
        if message is None:
            self.message = []
        else:
            self.message = message
        if object is None:
            self.object = []
        else:
            self.object = object
    def factory(*args_, **kwargs_):
        if ExecutionResult.subclass:
            return ExecutionResult.subclass(*args_, **kwargs_)
        else:
            return ExecutionResult(*args_, **kwargs_)
    factory = staticmethod(factory)
    def get_message(self): return self.message
    def set_message(self, message): self.message = message
    def add_message(self, value): self.message.append(value)
    def insert_message(self, index, value): self.message[index] = value
    def get_object(self): return self.object
    def set_object(self, object): self.object = object
    def add_object(self, value): self.object.append(value)
    def insert_object(self, index, value): self.object[index] = value
    def get_status(self): return self.status
    def set_status(self, status): self.status = status
    def get_local_dump_created(self): return self.local_dump_created
    def set_local_dump_created(self, local_dump_created): self.local_dump_created = local_dump_created
    def get_log_location(self): return self.log_location
    def set_log_location(self, log_location): self.log_location = log_location
    def get_export_dump_created(self): return self.export_dump_created
    def set_export_dump_created(self, export_dump_created): self.export_dump_created = export_dump_created
    def export(self, outfile, level, namespace_='', name_='ExecutionResult'):
        showIndent(outfile, level)
        outfile.write('<%s%s' % (namespace_, name_))
        self.exportAttributes(outfile, level, namespace_, name_='ExecutionResult')
        outfile.write('>\n')
        self.exportChildren(outfile, level + 1, namespace_, name_)
        showIndent(outfile, level)
        outfile.write('</%s%s>\n' % (namespace_, name_))
    def exportAttributes(self, outfile, level, namespace_='', name_='ExecutionResult'):
        outfile.write(' status="%s"' % str(self.get_status()))
        outfile.write(' local-dump-created="%s"' % str_lower(str(self.get_local_dump_created())))
        if self.get_log_location() is not None:
            outfile.write(' log-location="%s"' % (quote_attrib(self.get_log_location()), ))
        outfile.write(' export-dump-created="%s"' % str_lower(str(self.get_export_dump_created())))
    def exportChildren(self, outfile, level, namespace_='', name_='ExecutionResult'):
        for message_ in self.get_message():
            message_.export(outfile, level, namespace_, name_='message')
        for object_ in self.get_object():
            object_.export(outfile, level, namespace_, name_='object')
    def exportLiteral(self, outfile, level, name_='ExecutionResult'):
        level += 1
        self.exportLiteralAttributes(outfile, level, name_)
        self.exportLiteralChildren(outfile, level, name_)
    def exportLiteralAttributes(self, outfile, level, name_):
        showIndent(outfile, level)
        outfile.write('status = "%s",\n' % (self.get_status(),))
        showIndent(outfile, level)
        outfile.write('local_dump_created = "%s",\n' % (self.get_local_dump_created(),))
        showIndent(outfile, level)
        outfile.write('log_location = "%s",\n' % (self.get_log_location(),))
        showIndent(outfile, level)
        outfile.write('export_dump_created = "%s",\n' % (self.get_export_dump_created(),))
    def exportLiteralChildren(self, outfile, level, name_):
        showIndent(outfile, level)
        outfile.write('message=[\n')
        level += 1
        for message in self.message:
            showIndent(outfile, level)
            outfile.write('MessageType(\n')
            message.exportLiteral(outfile, level, name_='message')
            showIndent(outfile, level)
            outfile.write('),\n')
        level -= 1
        showIndent(outfile, level)
        outfile.write('],\n')
        showIndent(outfile, level)
        outfile.write('object=[\n')
        level += 1
        for object in self.object:
            showIndent(outfile, level)
            outfile.write('ObjectType(\n')
            object.exportLiteral(outfile, level, name_='object')
            showIndent(outfile, level)
            outfile.write('),\n')
        level -= 1
        showIndent(outfile, level)
        outfile.write('],\n')
    def build(self, node_):
        attrs = node_.attributes
        self.buildAttributes(attrs)
        for child_ in node_.childNodes:
            nodeName_ = child_.nodeName.split(':')[-1]
            self.buildChildren(child_, nodeName_)
    def buildAttributes(self, attrs):
        if attrs.get('status'):
            self.status = attrs.get('status').value
        if attrs.get('local-dump-created'):
            if attrs.get('local-dump-created').value in ('true', '1'):
                self.local_dump_created = True
            elif attrs.get('local-dump-created').value in ('false', '0'):
                self.local_dump_created = False
            else:
                raise ValueError('Bad boolean attribute (local-dump-created)')
        if attrs.get('log-location'):
            self.log_location = attrs.get('log-location').value
        if attrs.get('export-dump-created'):
            if attrs.get('export-dump-created').value in ('true', '1'):
                self.export_dump_created = True
            elif attrs.get('export-dump-created').value in ('false', '0'):
                self.export_dump_created = False
            else:
                raise ValueError('Bad boolean attribute (export-dump-created)')
    def buildChildren(self, child_, nodeName_):
        if child_.nodeType == Node.ELEMENT_NODE and \
            nodeName_ == 'message':
            obj_ = MessageType.factory()
            obj_.build(child_)
            self.message.append(obj_)
        elif child_.nodeType == Node.ELEMENT_NODE and \
            nodeName_ == 'object':
            obj_ = ObjectType.factory()
            obj_.build(child_)
            self.object.append(obj_)
# end class ExecutionResult


class ObjectType:
    subclass = None
    superclass = None
    def __init__(self, typexx='', name='', message=None, object=None):
        self.typexx = typexx
        self.name = name
        if message is None:
            self.message = []
        else:
            self.message = message
        if object is None:
            self.object = []
        else:
            self.object = object
    def factory(*args_, **kwargs_):
        if ObjectType.subclass:
            return ObjectType.subclass(*args_, **kwargs_)
        else:
            return ObjectType(*args_, **kwargs_)
    factory = staticmethod(factory)
    def get_message(self): return self.message
    def set_message(self, message): self.message = message
    def add_message(self, value): self.message.append(value)
    def insert_message(self, index, value): self.message[index] = value
    def get_object(self): return self.object
    def set_object(self, object): self.object = object
    def add_object(self, value): self.object.append(value)
    def insert_object(self, index, value): self.object[index] = value
    def get_type(self): return self.typexx
    def set_type(self, typexx): self.typexx = typexx
    def get_name(self): return self.name
    def set_name(self, name): self.name = name
    def export(self, outfile, level, namespace_='', name_='ObjectType'):
        showIndent(outfile, level)
        outfile.write('<%s%s' % (namespace_, name_))
        self.exportAttributes(outfile, level, namespace_, name_='ObjectType')
        outfile.write('>\n')
        self.exportChildren(outfile, level + 1, namespace_, name_)
        showIndent(outfile, level)
        outfile.write('</%s%s>\n' % (namespace_, name_))
    def exportAttributes(self, outfile, level, namespace_='', name_='ObjectType'):
        outfile.write(' type="%s"' % (quote_attrib(self.get_type()), ))
        outfile.write(' name="%s"' % (quote_attrib(self.get_name()), ))
    def exportChildren(self, outfile, level, namespace_='', name_='ObjectType'):
        for message_ in self.get_message():
            message_.export(outfile, level, namespace_, name_='message')
        for object_ in self.get_object():
            object_.export(outfile, level, namespace_, name_='object')
    def exportLiteral(self, outfile, level, name_='ObjectType'):
        level += 1
        self.exportLiteralAttributes(outfile, level, name_)
        self.exportLiteralChildren(outfile, level, name_)
    def exportLiteralAttributes(self, outfile, level, name_):
        showIndent(outfile, level)
        outfile.write('typexx = "%s",\n' % (self.get_type(),))
        showIndent(outfile, level)
        outfile.write('name = "%s",\n' % (self.get_name(),))
    def exportLiteralChildren(self, outfile, level, name_):
        showIndent(outfile, level)
        outfile.write('message=[\n')
        level += 1
        for message in self.message:
            showIndent(outfile, level)
            outfile.write('MessageType(\n')
            message.exportLiteral(outfile, level, name_='message')
            showIndent(outfile, level)
            outfile.write('),\n')
        level -= 1
        showIndent(outfile, level)
        outfile.write('],\n')
        showIndent(outfile, level)
        outfile.write('object=[\n')
        level += 1
        for object in self.object:
            showIndent(outfile, level)
            outfile.write('ObjectType(\n')
            object.exportLiteral(outfile, level, name_='object')
            showIndent(outfile, level)
            outfile.write('),\n')
        level -= 1
        showIndent(outfile, level)
        outfile.write('],\n')
    def build(self, node_):
        attrs = node_.attributes
        self.buildAttributes(attrs)
        for child_ in node_.childNodes:
            nodeName_ = child_.nodeName.split(':')[-1]
            self.buildChildren(child_, nodeName_)
    def buildAttributes(self, attrs):
        if attrs.get('type'):
            self.typexx = attrs.get('type').value
        if attrs.get('name'):
            self.name = attrs.get('name').value
    def buildChildren(self, child_, nodeName_):
        if child_.nodeType == Node.ELEMENT_NODE and \
            nodeName_ == 'message':
            obj_ = MessageType.factory()
            obj_.build(child_)
            self.message.append(obj_)
        elif child_.nodeType == Node.ELEMENT_NODE and \
            nodeName_ == 'object':
            obj_ = ObjectType.factory()
            obj_.build(child_)
            self.object.append(obj_)
# end class ObjectType


class MessageType:
    subclass = None
    superclass = None
    def __init__(self, code='', severity='', id='', description=None, resolution='', message=None):
        self.code = code
        self.severity = severity
        self.id = id
        self.description = description
        self.resolution = resolution
        self.message = message
    def factory(*args_, **kwargs_):
        if MessageType.subclass:
            return MessageType.subclass(*args_, **kwargs_)
        else:
            return MessageType(*args_, **kwargs_)
    factory = staticmethod(factory)
    def get_description(self): return self.description
    def set_description(self, description): self.description = description
    def validate_DescriptionType(self, value):
        # validate type DescriptionType
        pass
    def get_resolution(self): return self.resolution
    def set_resolution(self, resolution): self.resolution = resolution
    def get_message(self): return self.message
    def set_message(self, message): self.message = message
    def get_code(self): return self.code
    def set_code(self, code): self.code = code
    def get_severity(self): return self.severity
    def set_severity(self, severity): self.severity = severity
    def get_id(self): return self.id
    def set_id(self, id): self.id = id
    def export(self, outfile, level, namespace_='', name_='MessageType'):
        showIndent(outfile, level)
        outfile.write('<%s%s' % (namespace_, name_))
        self.exportAttributes(outfile, level, namespace_, name_='MessageType')
        outfile.write('>\n')
        self.exportChildren(outfile, level + 1, namespace_, name_)
        showIndent(outfile, level)
        outfile.write('</%s%s>\n' % (namespace_, name_))
    def exportAttributes(self, outfile, level, namespace_='', name_='MessageType'):
        if self.get_code() is not None:
            outfile.write(' code="%s"' % (quote_attrib(self.get_code()), ))
        outfile.write(' severity="%s"' % (quote_attrib(self.get_severity()), ))
        if self.get_id() is not None:
            outfile.write(' id="%s"' % (quote_attrib(self.get_id()), ))
    def exportChildren(self, outfile, level, namespace_='', name_='MessageType'):
        if self.description:
            self.description.export(outfile, level, namespace_, name_='description', )
        if self.get_resolution() != None :
            if self.get_resolution() != "" :
                showIndent(outfile, level)
                outfile.write('<%sresolution>%s</%sresolution>\n' % (namespace_, quote_xml(self.get_resolution()), namespace_))
        if self.get_message() != None :
            if self.message:
                self.message.export(outfile, level, namespace_, name_='message')
    def exportLiteral(self, outfile, level, name_='MessageType'):
        level += 1
        self.exportLiteralAttributes(outfile, level, name_)
        self.exportLiteralChildren(outfile, level, name_)
    def exportLiteralAttributes(self, outfile, level, name_):
        showIndent(outfile, level)
        outfile.write('code = "%s",\n' % (self.get_code(),))
        showIndent(outfile, level)
        outfile.write('severity = "%s",\n' % (self.get_severity(),))
        showIndent(outfile, level)
        outfile.write('id = "%s",\n' % (self.get_id(),))
    def exportLiteralChildren(self, outfile, level, name_):
        if self.description:
            showIndent(outfile, level)
            outfile.write('description=DescriptionType(\n')
            self.description.exportLiteral(outfile, level, name_='description')
            showIndent(outfile, level)
            outfile.write('),\n')
        showIndent(outfile, level)
        outfile.write('resolution=%s,\n' % quote_python(self.get_resolution()))
        if self.message:
            showIndent(outfile, level)
            outfile.write('message=MessageType(\n')
            self.message.exportLiteral(outfile, level, name_='message')
            showIndent(outfile, level)
            outfile.write('),\n')
    def build(self, node_):
        attrs = node_.attributes
        self.buildAttributes(attrs)
        for child_ in node_.childNodes:
            nodeName_ = child_.nodeName.split(':')[-1]
            self.buildChildren(child_, nodeName_)
    def buildAttributes(self, attrs):
        if attrs.get('code'):
            self.code = attrs.get('code').value
        if attrs.get('severity'):
            self.severity = attrs.get('severity').value
        if attrs.get('id'):
            self.id = attrs.get('id').value
    def buildChildren(self, child_, nodeName_):
        if child_.nodeType == Node.ELEMENT_NODE and \
            nodeName_ == 'description':
            obj_ = DescriptionType.factory()
            obj_.build(child_)
            self.set_description(obj_)
            self.validate_DescriptionType(self.description)    # validate type DescriptionType
        elif child_.nodeType == Node.ELEMENT_NODE and \
            nodeName_ == 'resolution':
            resolution_ = ''
            for text__content_ in child_.childNodes:
                resolution_ += text__content_.nodeValue
            self.resolution = resolution_
        elif child_.nodeType == Node.ELEMENT_NODE and \
            nodeName_ == 'message':
            obj_ = MessageType.factory()
            obj_.build(child_)
            self.set_message(obj_)
# end class MessageType


class DescriptionType:
    subclass = None
    superclass = None
    def __init__(self, encoding='', valueOf_=''):
        self.encoding = encoding
        self.valueOf_ = valueOf_
    def factory(*args_, **kwargs_):
        if DescriptionType.subclass:
            return DescriptionType.subclass(*args_, **kwargs_)
        else:
            return DescriptionType(*args_, **kwargs_)
    factory = staticmethod(factory)
    def get_encoding(self): return self.encoding
    def set_encoding(self, encoding): self.encoding = encoding
    def getValueOf_(self): return self.valueOf_
    def setValueOf_(self, valueOf_): self.valueOf_ = valueOf_
    def export(self, outfile, level, namespace_='', name_='DescriptionType'):
        showIndent(outfile, level)
        outfile.write('<%s%s' % (namespace_, name_))
        self.exportAttributes(outfile, level, namespace_, name_='DescriptionType')
        outfile.write('>\n')
        self.exportChildren(outfile, level + 1, namespace_, name_)
        outfile.write('</%s%s>\n' % (namespace_, name_))
    def exportAttributes(self, outfile, level, namespace_='', name_='DescriptionType'):
        if self.get_encoding() is not None:
            outfile.write(' encoding="%s"' % (quote_attrib(self.get_encoding()), ))
    def exportChildren(self, outfile, level, namespace_='', name_='DescriptionType'):
        outfile.write(quote_xml('%s' % self.valueOf_))
    def exportLiteral(self, outfile, level, name_='DescriptionType'):
        level += 1
        self.exportLiteralAttributes(outfile, level, name_)
        self.exportLiteralChildren(outfile, level, name_)
    def exportLiteralAttributes(self, outfile, level, name_):
        showIndent(outfile, level)
        outfile.write('encoding = "%s",\n' % (self.get_encoding(),))
    def exportLiteralChildren(self, outfile, level, name_):
        showIndent(outfile, level)
        outfile.write('valueOf_ = "%s",\n' % (self.valueOf_,))
    def build(self, node_):
        attrs = node_.attributes
        self.buildAttributes(attrs)
        self.valueOf_ = ''
        for child_ in node_.childNodes:
            nodeName_ = child_.nodeName.split(':')[-1]
            self.buildChildren(child_, nodeName_)
    def buildAttributes(self, attrs):
        if attrs.get('encoding'):
            self.encoding = attrs.get('encoding').value
    def buildChildren(self, child_, nodeName_):
        if child_.nodeType == Node.TEXT_NODE:
            self.valueOf_ += child_.nodeValue
# end class DescriptionType


from xml.sax import handler, make_parser

class SaxStackElement:
    def __init__(self, name='', obj=None):
        self.name = name
        self.obj = obj
        self.content = ''

#
# SAX handler
#
class Sax_ExecutionResultMixedHandler(handler.ContentHandler):
    def __init__(self):
        self.stack = []
        self.root = None

    def getRoot(self):
        return self.root

    def setDocumentLocator(self, locator):
        self.locator = locator
    
    def showError(self, msg):
        print '*** (showError):', msg
        sys.exit(-1)

    def startElement(self, name, attrs):
        done = 0
        if name == 'ExecutionResultMixed':
            obj = ExecutionResultMixed.factory()
            stackObj = SaxStackElement('ExecutionResultMixed', obj)
            self.stack.append(stackObj)
            done = 1
        elif name == 'backup':
            obj = ExecutionResult.factory()
            stackObj = SaxStackElement('backup', obj)
            self.stack.append(stackObj)
            done = 1
        elif name == 'transfer':
            obj = ExecutionResult.factory()
            stackObj = SaxStackElement('transfer', obj)
            self.stack.append(stackObj)
            done = 1
        elif name == 'restore':
            obj = ExecutionResultRestore.factory()
            stackObj = SaxStackElement('restore', obj)
            self.stack.append(stackObj)
            done = 1
        elif name == 'conflict-resolve':
            obj = ExecutionResult.factory()
            stackObj = SaxStackElement('conflict_resolve', obj)
            self.stack.append(stackObj)
            done = 1
        elif name == 'import':
            obj = ExecutionResult.factory()
            stackObj = SaxStackElement('importxx', obj)
            self.stack.append(stackObj)
            done = 1
        elif name == 'deploy':
            obj = ExecutionResult.factory()
            stackObj = SaxStackElement('deploy', obj)
            self.stack.append(stackObj)
            done = 1
        elif name == 'message':
            obj = MessageType.factory()
            stackObj = SaxStackElement('message', obj)
            self.stack.append(stackObj)
            done = 1
        elif name == 'object':
            obj = ObjectType.factory()
            stackObj = SaxStackElement('object', obj)
            self.stack.append(stackObj)
            done = 1
        elif name == 'description':
            obj = DescriptionType.factory()
            stackObj = SaxStackElement('description', obj)
            self.stack.append(stackObj)
            done = 1
        elif name == 'resolution':
            stackObj = SaxStackElement('resolution', None)
            self.stack.append(stackObj)
            done = 1
        if not done:
            self.reportError('"%s" element not allowed here.' % name)

    def endElement(self, name):
        done = 0
        if name == 'ExecutionResultMixed':
            if len(self.stack) == 1:
                self.root = self.stack[-1].obj
                self.stack.pop()
                done = 1
        elif name == 'backup':
            if len(self.stack) >= 2:
                self.stack[-2].obj.set_backup(self.stack[-1].obj)
                self.stack.pop()
                done = 1
        elif name == 'transfer':
            if len(self.stack) >= 2:
                self.stack[-2].obj.set_transfer(self.stack[-1].obj)
                self.stack.pop()
                done = 1
        elif name == 'restore':
            if len(self.stack) >= 2:
                self.stack[-2].obj.set_restore(self.stack[-1].obj)
                self.stack.pop()
                done = 1
        elif name == 'conflict-resolve':
            if len(self.stack) >= 2:
                self.stack[-2].obj.set_conflict_resolve(self.stack[-1].obj)
                self.stack.pop()
                done = 1
        elif name == 'import':
            if len(self.stack) >= 2:
                self.stack[-2].obj.set_import(self.stack[-1].obj)
                self.stack.pop()
                done = 1
        elif name == 'deploy':
            if len(self.stack) >= 2:
                self.stack[-2].obj.set_deploy(self.stack[-1].obj)
                self.stack.pop()
                done = 1
        elif name == 'message':
            if len(self.stack) >= 2:
                self.stack[-2].obj.add_message(self.stack[-1].obj)
                self.stack.pop()
                done = 1
        elif name == 'object':
            if len(self.stack) >= 2:
                self.stack[-2].obj.add_object(self.stack[-1].obj)
                self.stack.pop()
                done = 1
        elif name == 'description':
            if len(self.stack) >= 2:
                self.stack[-2].obj.set_description(self.stack[-1].obj)
                self.stack.pop()
                done = 1
        elif name == 'resolution':
            if len(self.stack) >= 2:
                content = self.stack[-1].content
                self.stack[-2].obj.set_resolution(content)
                self.stack.pop()
                done = 1
        if not done:
            self.reportError('"%s" element not allowed here.' % name)

    def characters(self, chrs, start, end):
        if len(self.stack) > 0:
            self.stack[-1].content += chrs[start:end]

    def reportError(self, mesg):
        locator = self.locator
        sys.stderr.write('Doc: %s  Line: %d  Column: %d\n' % \
            (locator.getSystemId(), locator.getLineNumber(), 
            locator.getColumnNumber() + 1))
        sys.stderr.write(mesg)
        sys.stderr.write('\n')
        sys.exit(-1)
        #raise RuntimeError

USAGE_TEXT = """
Usage: python <Parser>.py [ -s ] <in_xml_file>
Options:
    -s        Use the SAX parser, not the minidom parser.
"""

def usage():
    print USAGE_TEXT
    sys.exit(-1)


#
# SAX handler used to determine the top level element.
#
class SaxSelectorHandler(handler.ContentHandler):
    def __init__(self):
        self.topElementName = None
    def getTopElementName(self):
        return self.topElementName
    def startElement(self, name, attrs):
        self.topElementName = name
        raise StopIteration


def parseSelect(inFileName):
    infile = file(inFileName, 'r')
    topElementName = None
    parser = make_parser()
    documentHandler = SaxSelectorHandler()
    parser.setContentHandler(documentHandler)
    try:
        try:
            parser.parse(infile)
        except StopIteration:
            topElementName = documentHandler.getTopElementName()
        if topElementName is None:
            raise RuntimeError, 'no top level element'
        topElementName = topElementName.replace('-', '_').replace(':', '_')
        if topElementName not in globals():
            raise RuntimeError, 'no class for top element: %s' % topElementName
        topElement = globals()[topElementName]
        infile.seek(0)
        doc = minidom.parse(infile)
    finally:
        infile.close()
    rootNode = doc.childNodes[0]
    rootObj = topElement.factory()
    rootObj.build(rootNode)
    # Enable Python to collect the space used by the DOM.
    doc = None
    sys.stdout.write('<?xml version="1.0" ?>\n')
    rootObj.export(sys.stdout, 0)
    return rootObj


def saxParse(inFileName):
    parser = make_parser()
    documentHandler = Sax_ExecutionResultMixedHandler()
    parser.setDocumentHandler(documentHandler)
    parser.parse('file:%s' % inFileName)
    root = documentHandler.getRoot()
    sys.stdout.write('<?xml version="1.0" ?>\n')
    root.export(sys.stdout, 0)
    return root


def saxParseString(inString):
    parser = make_parser()
    documentHandler = Sax_ExecutionResultMixedHandler()
    parser.setDocumentHandler(documentHandler)
    parser.feed(inString)
    parser.close()
    rootObj = documentHandler.getRoot()
    #sys.stdout.write('<?xml version="1.0" ?>\n')
    #rootObj.export(sys.stdout, 0)
    return rootObj


def parse(inFileName):
    doc = minidom.parse(inFileName)
    rootNode = doc.documentElement
    rootObj = ExecutionResultMixed.factory()
    rootObj.build(rootNode)
    # Enable Python to collect the space used by the DOM.
    doc = None
    sys.stdout.write('<?xml version="1.0" ?>\n')
    rootObj.export(sys.stdout, 0, name_="ExecutionResultMixed")
    return rootObj


def parseString(inString):
    doc = minidom.parseString(inString)
    rootNode = doc.documentElement
    rootObj = ExecutionResultMixed.factory()
    rootObj.build(rootNode)
    # Enable Python to collect the space used by the DOM.
    doc = None
    sys.stdout.write('<?xml version="1.0" ?>\n')
    rootObj.export(sys.stdout, 0, name_="ExecutionResultMixed")
    return rootObj


def parseLiteral(inFileName):
    doc = minidom.parse(inFileName)
    rootNode = doc.documentElement
    rootObj = ExecutionResultMixed.factory()
    rootObj.build(rootNode)
    # Enable Python to collect the space used by the DOM.
    doc = None
    sys.stdout.write('from execution_result import *\n\n')
    sys.stdout.write('rootObj = ExecutionResultMixed(\n')
    rootObj.exportLiteral(sys.stdout, 0, name_="ExecutionResultMixed")
    sys.stdout.write(')\n')
    return rootObj


def main():
    args = sys.argv[1:]
    if len(args) == 2 and args[0] == '-s':
        saxParse(args[1])
    elif len(args) == 1:
        parse(args[0])
    else:
        usage()


if __name__ == '__main__':
    main()
    #import pdb
    #pdb.run('main()')

