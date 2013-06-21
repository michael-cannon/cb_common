#--------------------------------#
# ABOUT
#--------------------------------#

fileupload-class.php can be used to upload image and text files with a web 
browser. The uploaded file's name will get cleaned up - special characters
will be deleted, and spaces get replaced with underscores, and moved to 
a specified directory (on your server). fileupload-class.php also does its
best to determine the file's type (text, GIF, JPEG, etc). If the user
has named the file with the correct extension (.txt, .gif, etc), then
the class will use that, but if the user tries to upload an ex tensionless
file, PHP does can identify text, gif, jpeg, and png files for you. As
a last resort, if there is no specified extension, and PHP can not 
determine the type, you can set a default extension to be added.

#--------------------------------#
# REQUIREMENTS
#--------------------------------#

To run and have fun with fileupload-class.php you will need access to a 
Unix/Linux/*nix web server running Apache with the PHP module, a web 
browser that supports uploading (like Netscape), and the 2 other files 
that came with this. 

#--------------------------------#
# QUICK SETUP
#--------------------------------#

(1) Make a new directory within your with you web directory 
called "fileupload"

(2) Upload the files "fileupload-class.php" and "upload.php" to your 
fileupload direcotory

(3) Make another directory within the "fileupload" directory called "uploads"
and give it enough upload permissions for you web server to upload to it.
(usually, this means making it world writable)
 - cd /your/web/dir/fileupload
 - mkdir uploads
 - chmod 777 uploads
4) Fire up Netscape of IE and hit test it out:
   http://www.yourdomain.com/fileupload/upload.php
 
#--------------------------------#
# DETAILED INSTRUCTIONS
#--------------------------------#

You should have downloaded 3 files and 1 folder:
 - README.txt (this file)
 - fileupload-class.php
 - upload.php
 - uploads (folder)

fileupload-class.php:
    This is the file that does all the work. You shouldn't have to 
    change anything in this file.

upload.php
    Here's where all the variables reside (see below for an explainations).

VARIABLES in "upload.php":

// The path to the directory where you want the 
// uploaded files to be saved. This MUST end with a 
// trailing slash unless you use $path = ""; to 
// upload to the current directory. Whatever directory
// you choose, please chmod 777 that directory.

	$path = "uploads/";

// The name of the file field in your form.

	$upload_file_name = "userfile";

// ACCEPT mode - if you only want to accept
// a certain type of file.
// possible file types that PHP recognizes includes:
//
// OPTIONS INCLUDE:
//  text/plain
//  image/gif
//  image/jpeg
//  image/png
	
	// Accept ONLY gif's
	# $acceptable_file_types = "image/gif";
	
	// Accept GIF and JPEG files
	# $acceptable_file_types = "image/gif|image/jpeg|image/pjpeg";

	// Accept ALL files
	$acceptable_file_types = "";

// If no extension is supplied, and the browser or PHP
// can not figure out what type of file it is, you can
// add a default extension - like ".jpg" or ".txt"

	$default_extension = "";

// MODE: if your are attempting to upload
// a file with the same name as another file in the
// $path directory
//
// OPTIONS:
//   1 = overwrite mode
//   2 = create new with incremental extention
//   3 = do nothing if exists, highest protection

	$mode = 1;




#--------------------------------#
# LICENSE
#--------------------------------#

/*
///// fileupload-class.php /////
Copyright (c) 1999, 2002 David Fox, Angryrobot Productions
(http://www.angryrobot.com) All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:
1. Redistributions of source code must retain the above copyright notice,
this list of conditions and the following disclaimer.
2. Redistributions in binary form must reproduce the above copyright notice,
this list of conditions and the following disclaimer in the documentation
and/or other materials provided with the distribution.
3. Neither the name of author nor the names of its contributors may be used
to endorse or promote products derived from this software without specific
prior written permission.

DISCLAIMER:
THIS SOFTWARE IS PROVIDED BY THE AUTHOR AND CONTRIBUTORS "AS IS" AND ANY
EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED.  IN NO EVENT SHALL THE AUTHOR OR CONTRIBUTORS BE LIABLE FOR ANY
DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/
