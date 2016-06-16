TESTS = URLEncoderTest
GTEST_DIR = ../thirdparty/googletest/googletest
check_PROGRAMS = URLEncoderTest
LDADD = ${PTHREAD_LIBS} libgtest.a
AM_CXXFLAGS = ${PTHREAD_CFLAGS}
URLEncoderTest_CPPFLAGS =-I$(top_srcdir)/src/util -I$(GTEST_DIR)/include
URLEncoderTest_SOURCES = URLEncoderTest.cxx ../src/util/URLEncoder.cxx

libgtest.a:
	g++ -isystem $(GTEST_DIR)/include -I$(GTEST_DIR) -pthread -c $(GTEST_DIR)/src/gtest-all.cc
	ar -rv libgtest.a gtest-all.o

clean-local:
	rm -f libgtest.a

