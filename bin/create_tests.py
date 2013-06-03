#!/usr/bin/python

import sys
import os
import fnmatch
from time import gmtime,strftime
#
# Helper script to create test templates from application code
#
pattern="*.php"
TESTDIR_NAME="tests"

def mkdirs_graceful(testtarget):
    dirtree = []
    curdir = os.path.dirname(testtarget)
    while not os.path.exists(curdir):
        dirtree.insert(0,curdir)
        curdir = os.path.dirname(curdir)

    for i in dirtree:
        os.mkdir(i)

class TestClassFile(object):

    def __init__(self):
        self.namespace = ""
        self.uses = []
        self.in_class = False
        self.is_abstract = False
        self.classname = ""
        self.test_methods = []
        self.test_static_methods = []

    def write_test_class(self,filed):
        lines = []
        lines.append("<?php\n")
        lines.append("\n")
        if self.namespace:
            lines.append("namespace %s\n" % self.namespace)
        lines.append("/**\n")
        lines.append("*\n") 
        lines.append("* Test class for %s \n"  % self.classname.title()) 
        lines.append("* Created %s \n" % strftime("%a, %d %b %Y %H:%M:%S +0000", gmtime()))
        lines.append("*\n")
        lines.append("**/\n")
        lines.append("class %s extends \PHPUnit_Framework_TestCase\n" % (self.classname.title()+"Test"))
        lines.append("{\n\n")
        
        if not self.is_abstract: 
            for method in self.test_methods: 
                self.write_testmethod(lines,method) 
        for method in self.test_static_methods:
            self.write_testmethod(lines,method,True) 
            
        lines.append("}\n")
        filed.writelines(lines)
  
    def write_testmethod(self,lines,method,static=False):
        if method == "__construct":
            return
        method = method[0].upper()+method[1:]
        lines.append("    /**\n")
        lines.append("    * Test for %s::%s() \n" % (self.classname,method))
        if static:
            lines.append("    * Note: This method is static! \n" )
        lines.append("    *\n") 
        lines.append("    **/\n")
        lines.append("    public function test%s()\n" % method)
        lines.append("    {\n")
        lines.append("        $this->markTestIncomplete('test%s is not implemented yet');\n" % method)
        lines.append("    }\n\n")
   
def get_test_definition(filename):
    file_hdl = open(filename,"r")
    t = TestClassFile()
    ignorenext = False
    for line in file_hdl:
        line = line.strip()
        if "@dont_test" in line:
            ignorenext = True
            continue
        
        if line.startswith("namespace") and not t.in_class:
            t.namespace = "Tests\\"+line[len("namespace"):].strip()
            continue
        if line.startswith("use") and not t.in_class:
            t.uses.append(line[len("uses"):].strip())
            continue
        if line.startswith("abstract class") and not t.in_class:
            if ignorenext:
                ignorenext = False
                continue
            t.in_class = True
            t.is_abstract = True
            t.classname = line[len("abstract class"):].strip().split(" ")[0]
            continue
        if line.startswith("class") and not t.in_class:
            if ignorenext:
                ignorenext = False
                continue
            t.in_class = True
            t.is_abstract = False
            t.classname = line[len("class"):].strip().split(" ")[0]
            continue
        if t.in_class and line.startswith("public"):
            tokens = line.split(" ")
            if not "function" in tokens:
                continue
            is_static = "static" in tokens
            if ignorenext:
                ignorenext = False
                continue

            lasttoken = ""
            for token in tokens:
                method = None
                if token.startswith("("):
                    method = lasttoken
                else:
                    if "(" in token:
                        method = token.partition("(")[0]
                if method:
                    if is_static:
                        t.test_static_methods.append(method)
                    else:
                        t.test_methods.append(method)   
                    break
         
    return t 

if len(sys.argv) < 2:
    print "Usage: %s file_or_dir [pattern]\nPattern is %s by default\n" % (sys.argv[0],pattern)
    sys.exit(1)

bpath = os.path.abspath(sys.argv[1])
if not os.path.exists(bpath):
    print "Path %s could not be found or read!" % bpath
    sys.exit(1)

if len(sys.argv) > 2:
    pattern = sys.argv[2]

base="."
while not os.path.exists("%s/%s" % (base,TESTDIR_NAME)):
    if os.path.abspath("%s" % (base)) == "/":
        print "Can't find %s directory in this folder or any of it's parents"  % TESTDIR_NAME
        sys.exit(1)
    else:
        base = "../%s" % base
   

testdir = os.path.abspath("%s/%s" % (base,TESTDIR_NAME))

print "Found testdir under %s" % os.path.abspath("%s/%s" % (base,TESTDIR_NAME))
prefix =  os.path.commonprefix((bpath,testdir))
if prefix == "/":
    print "Test and source dir should be in the same prefix!"
    exit(1)

filelist = []
for root,dirs,files in os.walk(bpath):
    filtered = [i for i in files if fnmatch.fnmatch(i,pattern)]
    if not filtered:
        continue
    filelist += ["%s/%s" % (root,i) for i in filtered] 

for filename in filelist:
    
    test = get_test_definition(filename)
    if not test.in_class:
        print "Omitting %s, no class found" % filename
        continue
    if not test.test_static_methods and (test.is_abstract or not test.test_methods):
        print "Omitting %s, no public methods to test" % filename
        continue
    filename,ext = os.path.splitext(filename)
    testtarget = "%s/%sTest%s" % (testdir,filename[len(prefix):],ext)
    if os.path.exists(testtarget):
        print "Omitting %s as there already exists a test for it" % testtarget
        continue
    print "Creating %s" % testtarget
    mkdirs_graceful(testtarget)
    test.write_test_class(open(testtarget,"w"))
