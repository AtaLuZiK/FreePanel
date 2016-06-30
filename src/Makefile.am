bin_PROGRAMS = freepaneld
freepaneld_LDADD = ${PTHREAD_LIBS} ${LIBCONFIGXX_LIBS} ${LIBLOG4CXX_LIBS}
freepaneld_CPPFLAGS = -DSYSCONFDIR=\"${sysconfdir}\" -DLOCALSTATEDIR=\"${localstatedir}\"
freepaneld_CXXFLAGS = \
    -I$(top_srcdir)/thirdparty/rapidjson/include \
    ${PTHREAD_CFLAGS} ${LIBCONFIGXX_CFLAGS} \
    -include exception \
    -include string \
    -include stddef.h \
    -include unistd.h
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
    VirtualHostConfig.h VirtualHostConfig.cxx \
    IOSession.h IOSession.cxx \
    IOHandler.h IOHandler.cxx \
    FPProtocolHandler.h FPProtocolHandler.cxx \
    ${freepaneld_util_SOURCES} ${freepaneld_handler_SOURCES}

