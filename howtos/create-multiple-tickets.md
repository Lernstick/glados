## Create multiple tickets

A ticket is the access authorization for the testee to the exam. You can create a single ticket or multiple ones for an exam. Create a single ticket by the [Create single ticket](../ticket/create?mode=single) wizard and multiple tickets by the [Create multiple tickets](../ticket/create?mode=many&type=assigned) wizard.

-----

In the wizard you can put names of the students in the `Names` field. You can just copy the names from an external source such as an Excel file or another Office application. The names must be separated by a tab, comma, semicolon, newline or all of them combined. The field tries to read the names as you provide them. How the names are parsed, can be seen in the `Preview Proposal` on the right of the `Names` field. You can adjust the names, until the preview is as desired.

![Multiple tickets](img/multiple_tickets.gif)

After pressing `Create x tickets`, the tickets will be created with default values and you are being redirected to the exam view page. From there you can select `Actions->Generate PDFs` and you will see a PDF file containing all tickets that are in the open state (See [Ticket states](ticket-states.md)) of the current exam. This can be printed out, and provided to the students when taking the exam (See [Taking an Exam](take-exam.md)).