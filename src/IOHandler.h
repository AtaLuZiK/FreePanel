#ifndef IOHANDLER_H_
#define IOHANDLER_H_

#ifndef IOSESSION_H_
#error IOHandler.h requires IOSession.h to be included first
#endif


class IOHandler
{
public:
    IOHandler();
    virtual ~IOHandler();
    IOHandler(const IOHandler &) = delete;
    IOHandler& operator=(const IOHandler &) = delete;

    /**
     * Handles a new connection is created.
     * This function can be used to initialize session attributes, and perform one time activities for a particular connection.
     * This function is invoked from the I/O processor thread context, hence should be implemented in a way that it consumes minimal amount of time, as the same thread handles multiple sessions.
     * @param session
     */
    virtual void OnSessionCreated(IOSession& session);

    /**
     * Handles a session is closed.
     * When a session is closed. Session cleaning activities like cash cleanup can be performed here.
     * @param session
     */
    virtual void OnSessionClosed(IOSession& session);

    /**
     * Handles a data is received.
     * This is where the most of the processing of an application happens. You need to take care of all the data type you expect here.
     * @param session
     * @param data
     * @param length
     */
    virtual void OnDataReceived(IOSession& session, void *data, size_t length);

    /**
     * Handles a message aka response has been sent(calling IOSession.Write()).
     * @param session
     * @param data
     * @param length
     */
    virtual void OnDataSent(IOSession& session, void *data, size_t length);

    /**
     * Handles when an std::exception-derived is thrown.
     * The connection is closed if its code is io_errc::stream.
     * @param session
     * @param cause
     */
    virtual void OnExceptionCaught(IOSession& session, std::exception_ptr cause);

};

#endif /* IOHANDLER_H_ */
