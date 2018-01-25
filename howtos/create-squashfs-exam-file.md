##Create an exam with a squashfs-filesystem

You can create an exam using a squashfs-filesystem. This is useful when your exam has a more complex form than just a few files at some place in the system. All kinds of exam configurations are possible with a squashfs-filesystem.

----

The first step is to boot the computer from USB. See the following instructions:
* [Start from USB-Device (Mac)](https://wiki.lernstick.ch/doku.php?id=anleitungen:systemstart-mac)
* [Start from USB-Device (Windows 10)](https://wiki.lernstick.ch/doku.php?id=anleitungen:systemstart-uefi)

Please make sure that you choose `Datenpartition: lesen und schreiben`, before starting the system:

![Bootscreen](img/grub.png)

When the system has started, you can configure your exam. For example:

* install/deinstall specific applications
* preconfigure applications
* specific system configurations, which are not covered in the settings of the [Create Exam](../exam/create) wizard (Notice that, settings which are covered in the wizard will override settings you configure in the squashfs-filesystem).
* grant/deny advanced permissions to files and directories
* copy files needed for exam to their locations
* ...

----

When you are finished with the setup for the exam, restart your computer. This time it is important to choose `Datenpartition: nur lesen` from the start screen.

When the system has started, open the application `Speichermedienverwaltung`.

TODO