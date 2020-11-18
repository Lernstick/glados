## Ticket abandon time

Time in seconds of failed backup attempts until a ticket is abandoned (no more backups attempts are performed). This value applies only when no other values are set (See *Time Limit* in [Create a ticket](create-single-ticket.md) or in [Create an exam](create-exam.md)). If one of these values is set, the ticket will not be backuped anymore after the *Time Limit* has expired. To be abandoned the ticket must satisfy all the following:
<ul><li>Be in the Running state.</li>
<li>An IP address must be set.</li>
<li>A *Backup Interval* > 0 must be set.</li>
<li>If no *Time Limit* (in the ticket or exam) is set, the difference between the last successful backup and the last backup attempt must be geather than the configured <code>abandonTicket</code> time.</li>
<li>If a *Time Limit* is set (in the ticket or exam, if both are set the one from the ticket will be taken), the difference between the last successful backup and the last backup attempt must be greather than that *Time Limit*.</li></ul>