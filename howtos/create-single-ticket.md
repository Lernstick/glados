## Create a single ticket

A ticket is the access authorization for the student to his exam. If you have 20 students to take the exam, you have to generate 20 tickets (See [Create multiple tickets](create-multiple-tickets.md)). You can create a single ticket or multiple at once for a given exam. Create a single ticket by the `Actions->Create single ticket` wizard and multiple tickets by the `Actions->Create multiple tickets` wizard.

-----

The ticket *Token* (used to indentify the exam, see [Taking an Exam](take-exam.md)) is automatically generated. You can change it to a value of your desire, but notice that this value must be unique among all other ticket tokens (otherwise an error will occur).

*Backup Interval* describes the value (in seconds) for the backup schedule. It's the interval after which backup processes will run again on the ticket. 5 minutes is a moderate value for this.

> Notice, this will increase network traffic, if set to a very low value.

You can set a *Time Limit* (in minutes) for your exam, but this will have no indication (nothing will happen tough, if the time is up). In the [Ticket-view](ticket-view.md) can be seen whether the ticket is valid or not (time has expired).

> This will override the setting [configured in the exam](create-exam.md).

Each ticket can be assigned to a student in the *Test Taker* field.

Create the ticket by pressing `Create` at the bottom of the page. You will be redirected to the view page of the created ticket (See [The ticket view](ticket-view.md)).

Under `Actions->Generate PDF` you can generate a printable PDF file for this ticket. See the image below for an example ticket.

![PDF of ticket](img/ticket-pdf.png)

This PDF should be printed out and given to the student, when taking the exam (See [Taking an Exam](take-exam.md)).