#include "IOSession.h"
#include "IOHandler.h"


IOHandler::IOHandler()
{
}


IOHandler::~IOHandler()
{
}


void IOHandler::OnSessionCreated(IOSession& session)
{

}


void IOHandler::OnSessionClosed(IOSession& session)
{

}


void IOHandler::OnDataReceived(IOSession& session, void *data, size_t length)
{

}


void IOHandler::OnDataSent(IOSession& session, void *data, size_t length)
{

}


void IOHandler::OnExceptionCaught(IOSession& session, std::exception_ptr cause)
{
    std::rethrow_exception(cause);
}
