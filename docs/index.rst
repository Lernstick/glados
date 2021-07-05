==========
GLaDOS Documentation
==========

GLaDOS is a fully configurable webinterface to take, manage and create exams using the `Lernstick <https://www.digitale-nachhaltigkeit.unibe.ch/dienstleistungen/lernstick>`_.

The Project is available on `GitHub <https://github.com/imedias/glados>`_.

Features
==========

* Create exams in a :doc:`simple manner <howtos/create-zip-exam-file>`
* Create :doc:`complex exams <howtos/create-squashfs-exam-file>` with specific system configuration, additional software or permissions
* Manage your exams
* :doc:`Screen capturing <howtos/screen-capturing>` of the students screen
* Monitor exams in a :doc:`live view <howtos/monitoring-exams>`
* Configure backup intervals for exams
* :doc:`Restore specific files <howtos/restore-specific-file>` from the backup history during exams
* Generate conveniently :doc:`exam results <howtos/generate-results>` from the backups as a zip file
* :doc:`Submit <howtos/submit-results>` corrected exams back to the student

.. toctree::
   :caption: Howtos and Manuals
   :maxdepth: 2

   rtd-toc-exams
   rtd-toc-tickets
   howtos/take-exam
   rtd-toc-results

.. toctree::
   :caption: Example Exams
   :maxdepth: 2

   howtos/example-exam-essay

.. toctree::
   :caption: Troubleshooting
   :maxdepth: 2

   howtos/client-crash
   howtos/restore-specific-file

.. toctree::
   :caption: Installation
   :maxdepth: 2

   howtos/hardware-recommendations
   howtos/manual-install
   howtos/deb-install

.. toctree::
   :caption: Update and Upgrade
   :maxdepth: 2

   howtos/deb-update
   howtos/deb-8to9-upgrade

.. toctree::
   :caption: Configuration
   :maxdepth: 2

   howtos/config-files
   howtos/client-config
   howtos/network-config
   howtos/system-settings
   howtos/large-exams

.. toctree::
   :caption: Setting up Authentication
   :maxdepth: 2

   howtos/ldap-authentication
   howtos/ad-authentication-simple
   howtos/ad-authentication-advanced
   howtos/ldap-ssl
   howtos/test-login
   howtos/user-migration
   howtos/multiple-ldaps
   howtos/auth-placeholders
   howtos/login-scheme

