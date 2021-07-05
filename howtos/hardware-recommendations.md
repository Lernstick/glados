## Hardware Recommendations

The hardware recommendations are given in terms of CPUs, memory and disk space.

### Number of CPUs

Please consult the following table, where `n = (number of concurrent exams you wish to perform)`:

n             | # CPUs
------------  | -------------
1-50          | 1
50-100        | 2-3
100-200       | 3-4
200+          | 4+

### RAM

The required memory by GLaDOS can be calculated by the following formula:

```
Total Memory = (maximum number of running daemons)*(32MB) + 120MB
             + (number of concurrent exams you wish to perform)*(5MB) + 240MB
             + 1000MB
```

* Each daemon needs roughly 32MB of unshared memory. cThe amount of shared memory is approximately 120MB.
* Each event stream or agent needs roughly 5MB of unshared memerry. The amount of shared memory is approximately 240MB.

### Storage

The amount of disk storage needed by GLaDOS highly depends on various parameters:

* How long you wish to keep backups.
* How many data do students produce on average during an exam.
* How long an average exam will take.
* The setting of [Remote Backup Path](remote-backup-path.md).
* The setting of the screenshots interval.
* Whether you activate [Screen Capturing](screen-capturing) or not.

Make sure that there is enough disk storage for your needs.