ACLOCAL_AMFLAGS = ${ACLOCAL_FLAGS} -Im4
EXTRA_DIST = $(top_srcdir)/thirdparty docs/freepaneld.Doxyfile
SUBDIRS = . src tests
dist_sysconf_DATA = etc/freepaneld.cfg etc/freepaneld-log.properties

install-data-hook:
	@echo Installing configuration files
# freepaneld.cfg
	sed -i \
		-e 's|##APACHE_INSTALL_DIR##|$(APACHE_INSTALL_DIR)|g' \
		-e 's|##MYSQL_INSTALL_DIR##|$(MYSQL_INSTALL_DIR)|g' \
		-e 's|##PHP_INSTALL_DIR##|$(PHP_INSTALL_DIR)|g' \
		$(sysconfdir)/freepaneld.cfg
# freepaneld-log.properties
	sed -i \
		-e 's|##LOCALSTATEDIR##|$(localstatedir)|g' $(sysconfdir)/freepaneld-log.properties
	${install_sh} -d $(localstatedir)/log
	${install_sh} -d $(sysconfdir)/vhosts


@DX_RULES@

docs: doxygen-doc
.PHONY: docs

MOSTLYCLEANFILES = $(DX_CLEANFILES)

