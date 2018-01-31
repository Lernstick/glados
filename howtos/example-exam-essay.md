## Example exam: Essay

This is an example walktrough of an exam from A to Z. In this case it's a relatively simple exam; an essay. This article covers the whole process:

* creating the exam (including the file)
* generating tickets
* taking the exam
* generating results
* submit results back

### Goal

The final goal is that the student can write his essay using the [Lernstick-Prüfungsumgebung](https://imedias.ch/themen/lernstick/downloads.cfm) on his own notebook using wireless LAN. The testee should not be allowed to access the internet or any other network. The whole exam takes about 3 hours. Once the students handed their essays in, we want to retrieve all essays in a compact form for correction.

This example should also act as a future template for slightly modified or very similar exams.

So, let's get started.

### Creating the exam

First we create a new exam, by starting the `Create Exam` wizard under `Actions->Create Exam`.

Next we have to give our exam a *Name* and a *Subject*. I chose `Essay` and `Literature`, because we want to make a literature essay. You are completely free in how you name your exam. This is just for your to identify your exam. You can even use the same name multiple times (though I won't recommend it).

The *Time Limit* should be 3 hours, thus 180 minutes, because the time limit must be given in minutes.

The field *Remote Backup Path* will be left as it is, because we don't need to perform a backup for other paths.

In the `General` section, we only check *Take Screenshots* and set an *Interval* of `1 minute`. All other settings should be onchecked, since we don't need them.

In the section `Libreoffice`, check *Libreoffice: Save AutoRecovery information* again with an *Interval* of `1 minute` and also check *Libreoffice: Always create backup copy*. Those flags cause LibreOfiice to save a copy of the document all 1 minutes (to recover the document in case of a crash) and to create a backup copy of the document when saving. For more information, please refer to the ![Questionmark](img/questionmark.png) aside.

All fields are filled, thus press `Next step`.

----

Now we need to provide an `Exam File`. Since this is a simple exam (we don't need complex system configuration, such as new applications and special settings), we can use a zip file as exam file, as described in [Create an exam with a zip-file](create-zip-exam-file.md).

So we create a zip file on our local computer with the following directory structure:

	/home/
	/home/user/
	/home/user/Schreibtisch/
	/home/user/Schreibtisch/Hand-in/

Then we put in a file named `Essay Topic.pdf`, with instruction contents describing the essay to be written and where to save the files.

	/home/user/Schreibtisch/Essay Topic.pdf

Make sure, that you also instruct the student to save his/her essay in the directory `Hand-in` placed on the desktop. We created the directory for this purpose. Don't worry, everything the student produces will be backed up, but when [generating the exam results](generate-results.md), it will be much easier, if all exam results are placed in the same location.

Finally, the zip file is done. Of course you can provide more files and directories, if you want.

Now press `Add files...` in the form and upload the created zip file. Once the upload has finished, press `Update` below.

We have now created the exam, now we need to create tickets for the students to take the exam.

### Generating tickets

After pressing `Update` in the step before, we are now in the [exam view](exam-view.md).

I have a list of students, who should take the exam. Thus, in the exam view, press `Actions->Create assigned Tickets`. This is my example list (though, the essays might be of very high quality):

	Ray Bradbury
	James Joyce
	Leo Tolstoy
	Charles Dickens
	J. R. R. Tolkien
	George Orwell
	Jane Austen
	Mary Shelley
	Franz Kafka
	Agatha Christie

However you can copy the names into the `Names` field and see the preview on the right. For more information on creating multiple ticket please refer to [Create multiple tickets](create-multiple-tickets.md). Now press `Create 10 Tickets`.

We have now 10 Tickets (with names assigned to them) for our exam. Now we print the generated PDFs by clicking `Actions->Generate PDFs`. We now have a 10-paged PDF document with all created tickets in it. Every page has the form as seen in [Create a single ticket ](create-single-ticket.md). Print the document.

When browsing to `Tickets` in the navigation, you should now see the tickets:

![Ticket index](img/ticket-index.png)

As you can see, all the tickets are in the open state (see [Ticket states](ticket-states.md)) and no start and finish time is set.

We're ready to take the exam.

### Taking the exam

Pick one of the tickets and test your exam. This is fully described in [Taking an exam](take-exam.md), so please refer to this when starting the exam.

From now on I assume, you successfully started an exam with one of the tickets. This is how it should look like, after the desktop has loaded.

![Example exam](img/example-exam.png)

Notice, the `Essay Topic.pdf` file, we placed in the zip file earlier. There is also the `Hand-in` directory and a button `Finish exam` to finish the exam.

We now open LibreOffice Writer and write an example essay and save the file in the `Hand-in` directory.

![Example essay](img/example-essay.png)

![Example essay hand-in](img/example-hand-in.png)

Now we finish the exam, by pressing the `Finish exam` button (for details see [Taking an Exam](take-exam.md)).

We have tested the exam and now we continue by generating the exam results for correction.

### Generating exam results

TODO