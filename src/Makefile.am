bin_PROGRAMS = freepaneld
freepaneld_LDADD = ${PTHREAD_LIBS} ${LIBCONFIGXX_LIBS} ${LIBLOG4CXX_LIBS}
freepaneld_CPPFLAGS = -DSYSCONFDIR=\"${sysconfdir}\"
freepaneld_CXXFLAGS = \
    -I$(top_srcdir)/thirdparty/rapidjson/include \
    ${PTHREAD_CFLAGS} ${LIBCONFIGXX_CFLAGS}
freepaneld_util_SOURCES = \
    util/Singleton.h \
    util/URLEncoder.h util/URLEncoder.cxx
freepaneld_handler_SOURCES = \
    handler/NotFoundHandler.h handler/NotFoundHandler.cxx \
    handler/SettingsHandler.h handler/SettingsHandler.cxx \
    handler/SystemHandler.h handler/SystemHandler.cxx
freepaneld_SOURCES = \
    freepaneld.h \
    main.cxx \
    common.h common.cxx \
    AppConfig.h AppConfig.cxx \
    HandlerFactory.h HandlerFactory.cxx \
    Server.h Server.cxx \
    Handler.h \
    HttpPacket.h \
    FPPacket.h FPPacket.cxx \
    ${freepaneld_util_SOURCES} ${freepaneld_handler_SOURCES}

